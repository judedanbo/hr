<script setup>
import TableHead from "@/Components/TableHead.vue";
defineProps({
	align: {
		type: String,
		default: "left",
	},
});
</script>
<template>
	<td :class="'text-' + align" class="px-6 py-4 whitespace-nowrap">
		<slot />
	</td>
</template>
