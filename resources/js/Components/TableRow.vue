<script setup>
const emit = defineEmits(["rowClicked"]);
</script>
<template>
	<tr
		class="cursor-pointer transition-all hover:bg-gray-100 dark:hover:bg-gray-600 hover:shadow-lg"
		@click="emit('rowClicked')"
	>
		<slot />
	</tr>
</template>
