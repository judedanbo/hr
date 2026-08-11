import "./bootstrap";
import "../css/app.css";

import { createApp, h } from "vue";
import { createInertiaApp } from "@inertiajs/vue3";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { ZiggyVue } from "../../vendor/tightenco/ziggy/dist/vue.es.js";
import { plugin as formKitPlugin, defaultConfig } from "@formkit/vue";
import { createMultiStepPlugin } from "@formkit/addons";
import "@formkit/addons/css/multistep";
import { generateClasses } from "@formkit/themes";
import { search } from "@formkit/icons";
import { formKitTheme } from "./formkit/theme";

const appName =
	window.document.getElementsByTagName("title")[0]?.innerText || "HRMIS";

createInertiaApp({
	title: (title) => `${title} - ${appName}`,
	resolve: (name) =>
		resolvePageComponent(
			`./Pages/${name}.vue`,
			import.meta.glob("./Pages/**/*.vue"),
		),
	setup({ el, App, props, plugin }) {
		return createApp({ render: () => h(App, props) })
			.use(plugin)
			.use(ZiggyVue, props.initialPage.props.ziggy)
			.use(
				formKitPlugin,
				defaultConfig({
					plugins: [createMultiStepPlugin()],
					// Registered globally so `prefix-icon="search"` resolves; without
					// this the prop is accepted and silently renders nothing.
					icons: { search },
					config: {
						classes: generateClasses(formKitTheme),
					},
				}),
			)
			.mount(el);
	},
	progress: {
		color: "#228B02",
		showSpinner: true,
	},
});
