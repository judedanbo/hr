<script setup>
import { computed } from "vue";

const props = defineProps({
	variant: {
		type: String,
		default: "neutral",
		validator: (value) =>
			["neutral", "success", "warning", "danger", "info"].includes(value),
	},
});

// Low-alpha inset rings read correctly on both bg-white and bg-gray-800
// without needing a second set of colours.
const variants = {
	neutral:
		"bg-gray-50 text-gray-700 ring-gray-500/10 dark:bg-gray-700/50 dark:text-gray-300 dark:ring-gray-400/20",
	success:
		"bg-green-50 text-green-800 ring-green-600/20 dark:bg-green-500/10 dark:text-green-300 dark:ring-green-500/30",
	warning:
		"bg-amber-50 text-amber-800 ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/30",
	danger:
		"bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-500/10 dark:text-red-300 dark:ring-red-500/30",
	info: "bg-blue-50 text-blue-700 ring-blue-600/20 dark:bg-blue-500/10 dark:text-blue-300 dark:ring-blue-500/30",
};

const variantClass = computed(
	() => variants[props.variant] ?? variants.neutral,
);
</script>
<template>
	<span
		:class="[
			variantClass,
			'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset',
		]"
	>
		<slot />
	</span>
</template>
