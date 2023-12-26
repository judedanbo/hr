<script setup>
import TableHead from "@/Components/TableHead.vue";
import TableBody from "@/Components/TableBody.vue";
</script>
<template>
	<table class="min-w-full overflow-x-scroll divide-y divide-gray-200">
		<slot />
		<!-- <TableHead />
		<TableBody :data="" /> -->
	</table>
</template>
