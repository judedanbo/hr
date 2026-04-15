// Safe wrapper around chartjs-plugin-datalabels.
//
// v2.2.0 + Chart.js 4 has a known class of bugs where several plugin hooks
// (beforeUpdate, afterDatasetUpdate, beforeEvent, afterEvent, afterDatasetsDraw,
// etc.) dereference `chart[EXPANDO_KEY]` or its inner fields without guarding
// against the expando being absent. This happens during rapid mount/unmount
// cycles (our expand-to-modal flow triggers it) and late events fired during
// teardown.
//
// We wrap every known hook so the specific TypeError family gets swallowed,
// while any unrelated error still bubbles up.
import ChartDataLabels from "chartjs-plugin-datalabels";

const HOOKS = [
	"beforeInit",
	"beforeUpdate",
	"afterDatasetUpdate",
	"afterUpdate",
	"afterDatasetsDraw",
	"beforeEvent",
	"afterEvent",
	"beforeDestroy",
];

// The message differs per browser but always mentions one of these internal
// field names, which are unique to the plugin's expando object.
const EXPANDO_FIELD_RE =
	/_listened|_actives|_datasets|_labels|_listeners|_dirty|EXPANDO_KEY/i;

function isExpandoError(err) {
	return (
		err instanceof TypeError &&
		typeof err.message === "string" &&
		EXPANDO_FIELD_RE.test(err.message)
	);
}

for (const hook of HOOKS) {
	const original = ChartDataLabels[hook];
	if (typeof original !== "function") continue;
	ChartDataLabels[hook] = function safeHook(...args) {
		try {
			return original.apply(this, args);
		} catch (err) {
			if (isExpandoError(err)) return undefined;
			throw err;
		}
	};
}

export default ChartDataLabels;
