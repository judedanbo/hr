const defaultTheme = require("tailwindcss/defaultTheme");
const formKitTailwind = require("@formkit/themes/tailwindcss");

/** @type {import('tailwindcss').Config} */
module.exports = {
	darkMode: "class",
	content: [
		"./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
		"./storage/framework/views/*.php",
		"./resources/views/**/*.blade.php",
		// The FormKit theme lives in app.js, so .js must be scanned or most of
		// its classes are purged before they reach the browser.
		"./resources/js/**/*.{js,vue}",
	],

	theme: {
		extend: {
			fontFamily: {
				sans: ["Nunito", ...defaultTheme.fontFamily.sans],
			},
		},
	},

	plugins: [
		require("@tailwindcss/forms"),
		require('@tailwindcss/typography'), 
		formKitTailwind
	],
};
