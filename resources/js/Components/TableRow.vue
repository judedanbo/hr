<script setup>
import TableHead from "@/Components/TableHead.vue";
</script>
<template>
	<tr
		class="cursor-pointer transition-all hover:bg-gray-100 dark:hover:bg-gray-600 hover:shadow-lg"
	>
		<slot />
	</tr>
</template>
