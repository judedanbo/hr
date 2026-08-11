<script setup>
import { computed } from "vue";

const props = defineProps({
	align: {
		type: String,
		default: "left",
		validator: (value) => ["left", "center", "right"].includes(value),
	},
});

// Static map rather than 'text-' + align — see the note in TableData.vue.
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
	<th
		scope="col"
		:class="alignClass"
		class="px-4 py-2.5 text-xs font-semibold uppercase tracking-wide whitespace-nowrap text-gray-500 dark:text-gray-400"
	>
		<slot />
	</th>
</template>
