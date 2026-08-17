# Kubernetes deployment

Manifests for the HRMIS application on the `infosys` AKS cluster (resource group
`infosys`, region `uksouth`), namespace `hr`. Both environments share that one
namespace, which drives several decisions below.

```
k8s/
├── base/                     workloads shared by both environments
├── overlays/staging/         test-hr.audit.gov.gh
├── overlays/production/      hr.audit.gov.gh
└── migrate-job.yaml          rendered per release by the deploy workflow
```

Rendering is plain kustomize, so `kubectl kustomize k8s/overlays/production`
shows exactly what will be applied. Nothing here is templated by Helm.

## Naming and environment isolation

Because staging and production live in the same namespace, every workload is
prefixed (`hrmis-test-`, `hrmis-production-`) **and** every Service and
Deployment selector carries `app.kubernetes.io/instance`. Without that label in
the *selector*, the production Service would match staging pods and route real
traffic to them. `.github/workflows/manifests.yml` asserts this on every pull
request; treat a failure there as a genuine outage prevented, not a lint nit.

## Secrets

Secrets are created out of band and are **never** committed. Each environment
needs one Secret, referenced by name from its overlay:

| Environment | Secret |
|---|---|
| staging | `hrmis-test-app-secret` |
| production | `hrmis-production-app-secret` |

### Why new Secrets rather than the existing ones

The manifests use `envFrom: secretRef`, which turns **every key into an
environment variable under that exact key name**. The existing
`hrmis-production-secret` stores the database credentials under `username` and
`password`, because the old Deployment remapped them one by one with
`secretKeyRef`. Consumed via `envFrom`, those would arrive as `$username` and
`$password` — names Laravel never reads — and the application would come up
with no database credentials at all.

So the new Secrets use Laravel's own variable names as keys. Create them by
copying the live values across, without ever printing them:

```bash
OLD=hrmis-production-secret
NEW=hrmis-production-app-secret
get() { kubectl -n hr get secret "$OLD" -o jsonpath="{.data.$1}" | base64 -d; }

kubectl -n hr create secret generic "$NEW" \
  --from-literal=APP_KEY="$(get APP_KEY)" \
  --from-literal=DB_DATABASE="$(get DB_DATABASE)" \
  --from-literal=DB_USERNAME="$(get username)" \
  --from-literal=DB_PASSWORD="$(get password)" \
  --from-literal=MAIL_PASSWORD="$(get MAIL_PASSWORD)"
```

For staging the `APP_KEY` currently sits in **plaintext inside the
`hrmis-config-map` ConfigMap**; move it into the Secret as part of this work:

```bash
kubectl -n hr create secret generic hrmis-test-app-secret \
  --from-literal=APP_KEY="$(kubectl -n hr get cm hrmis-config-map -o jsonpath='{.data.APP_KEY}')" \
  --from-literal=DB_DATABASE=hrmis \
  --from-literal=DB_USERNAME="$(kubectl -n hr get secret hrmis-test-secret -o jsonpath='{.data.username}' | base64 -d)" \
  --from-literal=DB_PASSWORD="$(kubectl -n hr get secret hrmis-test-secret -o jsonpath='{.data.password}' | base64 -d)"
```

`PUSHER_*` and `AWS_*` are deliberately **not** carried over. The live
application runs with `broadcasting.default=log` and `filesystems.default=local`,
so those credentials are unused; leaving them out shrinks what a leak would cost.

## Non-secret configuration

`overlays/*/app.env` becomes a ConfigMap via `configMapGenerator`. Kustomize
appends a content hash to the name, so editing that file rolls the pods
automatically instead of leaving them running stale config.

Three values there are corrections to the previous setup rather than
transcriptions of it, and each is commented in place:

- `BROADCAST_DRIVER=log` — the old ConfigMap claimed `pusher`, but the key was
  never wired into the pod, so the app has always run `log`. Adopting `envFrom`
  would have silently switched broadcasting on.
- `TELESCOPE_ENABLED=false` — Telescope and Debugbar are in composer's `require`
  (not `require-dev`) and registered unconditionally, `--no-dev` still installs
  them, and `config/telescope.php` defaults `enabled` to **true**. Only the baked
  `.env` was keeping Telescope off; unbaking it without this line would turn it on
  in production and start writing every request to the database.
- `QUEUE_CONNECTION=redis` — the old `QUEUE_DRIVER` is Laravel 5 naming that
  Laravel 11 ignores. Every `app/Exports/*` class implements `ShouldQueue`, so
  this only works because a queue worker Deployment ships with it.

## One-time setup

1. Create a managed identity with a **federated credential** for this repository
   (OIDC — no stored Azure secret).
2. Grant it `AcrPush` on the `regisry` registry and
   `Azure Kubernetes Service Cluster User Role` on the `infosys` cluster.
3. Add repository secrets `AZURE_CLIENT_ID`, `AZURE_TENANT_ID`,
   `AZURE_SUBSCRIPTION_ID`.
4. Create GitHub Environments `staging` (unprotected) and `production` (required
   reviewers — this is the production gate).

> The cluster uses AKS **local accounts**, not Entra RBAC, so
> `Cluster User Role` grants cluster-wide access rather than access scoped to
> `hr`. Narrowing it means enabling Entra integration plus Azure RBAC for
> Kubernetes and assigning `Azure Kubernetes Service RBAC Writer` scoped to
> `/namespaces/hr` — a cluster-level change that also affects the `adla`,
> `tracker` and `gas` namespaces, so it is deliberately not bundled in here.

## Cutover

The new workloads are named differently from the current ones, so they run
**alongside** the existing stack rather than replacing it in place. Kustomize
cannot rename a resource through a patch, which means `kubectl diff` is not a
meaningful adoption check for the workloads — validate by port-forward instead.

Staging first, since it is already broken:

1. Create `hrmis-test-app-secret`.
2. Let the pipeline deploy staging.
3. Confirm cert-manager issued a real certificate for `test-hr.audit.gov.gh`
   through `infosys-issuer` — this is the fix for staging being unreachable.
4. Exercise login (Redis sessions), an Excel export (queue worker) and `/up`.

Then production, additive changes first:

1. Create `hrmis-production-app-secret`.
2. Deploy; Redis, queue and scheduler are new workloads and touch nothing live.
3. Validate the new stack privately before any traffic moves:
   `kubectl -n hr port-forward deploy/hrmis-production-web 8080:80`
4. Delete `hr-test-ingress` **in the same step** as creating the new production
   Ingress — it currently owns `hr.audit.gov.gh`, and two Ingresses claiming one
   host conflict in ingress-nginx.
5. Once healthy, delete the old `hrmis-production*` Deployments and Services and
   the `dep_hr-*-odd` / `dep_hr-*-even` ACR repositories.

Production keeps its existing `hrmis-test-letsencrypt-nginx` Issuer and
certificate secret for now. Moving it to `infosys-issuer` is a separate change,
made only after staging has proven that issuer works — Let's Encrypt's
production endpoint allows five duplicate certificates per week, so a failed
experiment there is expensive.

## Rollback

The deploy workflow runs `kubectl rollout undo` automatically when a rollout or
smoke test fails. **Migrations are not reverted** — automatic down-migrations
lose data, so a release that failed after migrating needs a human decision.

## Known deferred risk: uploads are not persisted

`patch-storage.yaml` reproduces the current volume mounts exactly, including
their gap. `prod-avatars-pvc` covers `storage/app/public/avatars` and nothing
else, while `config/filesystems.php` also writes to:

- `storage/app/public/qualifications`
- `storage/app/public/leave-documents`
- `storage/app/documents`

Those paths are on ephemeral container storage, so **every redeploy destroys the
documents uploaded since the last one**. This was true before this work and is
deliberately left unchanged here: altering the mounts as a side effect of the
CI/CD migration would be an untested data migration performed without a backup.

Automating deploys makes redeploys more frequent, which makes this bite more
often. The production approval gate limits the exposure but does not remove it.
The fix is a single RWX volume mounted at `storage/app` on both the php-fpm and
nginx containers, plus a one-off Job to copy the surviving files across before
cutover.
