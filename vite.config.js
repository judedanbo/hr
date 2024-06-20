import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";

export default defineConfig({
	define: {
		__VUE_PROD_HYDRATION_MISMATCH_DETAILS__: false,
	},
	server: {
		hmr: {
			host: "localhost",
		},
	},
	plugins: [
		laravel({
			input: "resources/js/app.js",
			refresh: true,
		}),
		vue({
			template: {
				transformAssetUrls: {
					base: null,
					includeAbsolute: false,
				},
			},
		}),
	],
});
