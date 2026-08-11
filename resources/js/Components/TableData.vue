<script setup>
import { computed } from "vue";

const props = defineProps({
	align: {
		type: String,
		default: "left",
		validator: (value) => ["left", "center", "right"].includes(value),
	},
	primary: {
		type: Boolean,
		default: false,
	},
	nowrap: {
		type: Boolean,
		default: true,
	},
});

// Static map rather than 'text-' + align: Tailwind cannot see concatenated
// class names, so the built string only resolved because these literals
// happen to appear elsewhere in the codebase.
const alignClasses = {
	left: "text-left",
	center: "text-center",
	right: "text-right",
};

const alignClass = computed(
	() => alignClasses[props.align] ?? alignClasses.left,
);
</script>
<template>
	<td
		:class="[
			alignClass,
			nowrap ? 'whitespace-nowrap' : '',
			primary
				? 'font-medium text-gray-900 dark:text-gray-50'
				: 'text-gray-700 dark:text-gray-200',
			'px-4 py-2.5 text-sm',
		]"
	>
		<slot />
	</td>
</template>
