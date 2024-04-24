<script setup>
defineProps({
	align: {
		type: String,
		default: "left",
	},
});
</script>
<template>
	<th
		scope="col"
		:class="'text-' + align"
		class="px-6 py-3 text-xs font-bold tracking-widest text-gray-800 dark:text-gray-50 uppercase"
	>
		<slot />
	</th>
</template>
