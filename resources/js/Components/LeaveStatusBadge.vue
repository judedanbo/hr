<script setup>
import { computed } from "vue";

const props = defineProps({
	status: { type: String, default: "" },
});

const classes = computed(() => {
	const map = {
		Draft: "bg-gray-100 text-gray-800",
		Pending: "bg-yellow-100 text-yellow-800",
		Approved: "bg-green-100 text-green-800",
		Declined: "bg-red-100 text-red-800",
		Cancelled: "bg-gray-100 text-gray-500",
		Completed: "bg-blue-100 text-blue-800",
	};
	return map[props.status] || "bg-gray-100 text-gray-800";
});
</script>

<template>
	<span :class="[classes, 'px-2 py-1 rounded-full text-xs font-medium']">
		{{ status }}
	</span>
</template>
