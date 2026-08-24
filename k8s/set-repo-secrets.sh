#!/usr/bin/env bash
#
# Sets the repository secrets the deploy workflow needs (step 3 of the one-time
# setup in k8s/README.md). Deployment authenticates to Azure with OIDC, so these
# three identifiers are the only secrets there are — no client secret is stored.
#
# Values are taken from the environment when already set, otherwise derived from
# the logged-in Azure CLI where that is possible, otherwise prompted for.
#
#   AZURE_CLIENT_ID        client ID of the managed identity holding the
#                          federated credential for this repository
#   AZURE_TENANT_ID        defaults to the current `az account` tenant
#   AZURE_SUBSCRIPTION_ID  defaults to the current `az account` subscription
#
# Optionally, set AZURE_IDENTITY_NAME and AZURE_IDENTITY_RESOURCE_GROUP to look
# the client ID up instead of pasting it.
#
# Usage:
#   k8s/set-repo-secrets.sh [--repo owner/name] [--dry-run] [--yes]

set -euo pipefail

REPO=""
DRY_RUN=false
ASSUME_YES=false

usage() {
    sed -n '2,/^set -euo/p' "$0" | sed 's/^# \{0,1\}//; $d'
}

while [ $# -gt 0 ]; do
    case "$1" in
        --repo)
            REPO="${2:-}"
            [ -n "$REPO" ] || { echo "--repo needs a value" >&2; exit 2; }
            shift 2
            ;;
        --repo=*)
            REPO="${1#*=}"
            shift
            ;;
        --dry-run)
            DRY_RUN=true
            shift
            ;;
        -y|--yes)
            ASSUME_YES=true
            shift
            ;;
        -h|--help)
            usage
            exit 0
            ;;
        *)
            echo "Unknown argument: $1" >&2
            usage >&2
            exit 2
            ;;
    esac
done

command -v gh >/dev/null 2>&1 || {
    echo "error: the GitHub CLI (gh) is required — https://cli.github.com" >&2
    exit 1
}

gh auth status >/dev/null 2>&1 || {
    echo "error: not logged in to GitHub. Run: gh auth login" >&2
    exit 1
}

if [ -z "$REPO" ]; then
    REPO="$(gh repo view --json nameWithOwner -q .nameWithOwner)"
fi

# Writing secrets needs admin on the repository; failing here is far clearer
# than three individual permission errors further down.
if ! gh api "repos/$REPO/actions/secrets" --silent >/dev/null 2>&1; then
    echo "error: cannot read Actions secrets on $REPO — admin access is required" >&2
    exit 1
fi

az_query() {
    command -v az >/dev/null 2>&1 || return 1
    az "$@" 2>/dev/null || return 1
}

# Echoes the resolved value; every prompt and message goes to stderr so the
# caller can capture the value cleanly.
resolve() {
    local name="$1" description="$2" default="${3:-}" value

    value="${!name:-}"

    if [ -z "$value" ] && [ -n "$default" ]; then
        value="$default"
        echo "  $name: using $description from the Azure CLI" >&2
    fi

    while [ -z "$value" ]; do
        if [ ! -t 0 ]; then
            echo "error: $name is not set and there is no terminal to prompt on" >&2
            exit 1
        fi
        printf '  %s (%s): ' "$name" "$description" >&2
        read -r value
    done

    # A Windows `az` reached through WSL interop terminates its output with
    # CRLF, and a pasted value often carries stray whitespace. Either would be
    # stored verbatim into the secret and fail authentication opaquely.
    value="$(printf '%s' "$value" | tr -d '\r\n' | sed 's/^[[:space:]]*//; s/[[:space:]]*$//')"

    # Every one of these is a GUID. Catching a mistyped or truncated paste now
    # beats a deploy failing on an opaque Azure login error later.
    if ! printf '%s' "$value" | grep -qiE '^[0-9a-f]{8}(-[0-9a-f]{4}){3}-[0-9a-f]{12}$'; then
        echo "error: $name does not look like a GUID: $value" >&2
        exit 1
    fi

    printf '%s' "$value"
}

echo "Repository: $REPO"
echo

identity_client_id=""
if [ -z "${AZURE_CLIENT_ID:-}" ] && [ -n "${AZURE_IDENTITY_NAME:-}" ] && [ -n "${AZURE_IDENTITY_RESOURCE_GROUP:-}" ]; then
    identity_client_id="$(az_query identity show \
        --name "$AZURE_IDENTITY_NAME" \
        --resource-group "$AZURE_IDENTITY_RESOURCE_GROUP" \
        --query clientId -o tsv || true)"
fi

client_id="$(resolve AZURE_CLIENT_ID 'managed identity client ID' "$identity_client_id")"
tenant_id="$(resolve AZURE_TENANT_ID 'Entra tenant ID' "$(az_query account show --query tenantId -o tsv || true)")"
subscription_id="$(resolve AZURE_SUBSCRIPTION_ID 'Azure subscription ID' "$(az_query account show --query id -o tsv || true)")"

echo
echo "About to set on $REPO:"
echo "  AZURE_CLIENT_ID       = $client_id"
echo "  AZURE_TENANT_ID       = $tenant_id"
echo "  AZURE_SUBSCRIPTION_ID = $subscription_id"
echo
echo "Existing values with these names are overwritten."

if [ "$DRY_RUN" = true ]; then
    echo
    echo "--dry-run: nothing was written."
    exit 0
fi

if [ "$ASSUME_YES" != true ]; then
    if [ ! -t 0 ]; then
        echo "error: refusing to write without confirmation; pass --yes for non-interactive use" >&2
        exit 1
    fi
    printf 'Continue? [y/N] '
    read -r reply
    case "$reply" in
        [yY]|[yY][eE][sS]) ;;
        *) echo "Aborted."; exit 1 ;;
    esac
fi

# Piped on stdin rather than passed as --body so the values never appear in the
# process list on a shared machine.
set_secret() {
    printf '%s' "$2" | gh secret set "$1" --repo "$REPO"
    echo "  set $1"
}

echo
set_secret AZURE_CLIENT_ID "$client_id"
set_secret AZURE_TENANT_ID "$tenant_id"
set_secret AZURE_SUBSCRIPTION_ID "$subscription_id"

echo
echo "Done. Remaining one-time setup (see k8s/README.md):"
echo "  - GitHub Environments 'staging' (unprotected) and 'production' (required reviewers)"
echo "  - the out-of-band Kubernetes Secrets hrmis-test-app-secret / hrmis-production-app-secret"
