// Safe wrapper around chartjs-plugin-datalabels.
//
// v2.2.0 has a known regression against Chart.js 4: the plugin's beforeDestroy
// hook dereferences chart[EXPANDO_KEY]._listened without guarding the case
// where the expando was never populated (e.g. when a chart is unmounted before
// its first render or during a rapid mount/unmount cycle in a modal).
//
// We wrap beforeDestroy so the expando-missing case becomes a no-op instead of
// a TypeError that crashes the dev overlay.
import ChartDataLabels from "chartjs-plugin-datalabels";

const originalBeforeDestroy = ChartDataLabels.beforeDestroy;

if (typeof originalBeforeDestroy === "function") {
	ChartDataLabels.beforeDestroy = function safeBeforeDestroy(...args) {
		try {
			return originalBeforeDestroy.apply(this, args);
		} catch (err) {
			// Specifically tolerate the expando-not-initialised TypeError.
			if (err instanceof TypeError && /_listened|EXPANDO_KEY/i.test(String(err.message))) {
				return undefined;
			}
			throw err;
		}
	};
}

export default ChartDataLabels;
