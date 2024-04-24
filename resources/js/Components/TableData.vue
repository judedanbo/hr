<script setup>
defineProps({
	align: {
		type: String,
		default: "left",
	},
});
</script>
<template>
	<td
		:class="'text-' + align"
		class="px-6 py-4 whitespace-nowrap text-green-900 dark:text-gray-50 font-semibold"
	>
		<slot />
	</td>
</template>
