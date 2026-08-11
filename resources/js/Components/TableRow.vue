<script setup>
const emit = defineEmits(["rowClicked"]);

defineProps({
	// Most rows in the app are not clickable, so this defaults to false.
	// Rows that navigate on click opt in, which also gives them keyboard access.
	clickable: {
		type: Boolean,
		default: false,
	},
});
</script>
<template>
	<tr
		:class="[
			clickable
				? 'cursor-pointer focus-visible:outline focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-green-600'
				: '',
			'transition-colors hover:bg-green-50/60 dark:hover:bg-gray-700/50',
		]"
		:tabindex="clickable ? 0 : undefined"
		@click="emit('rowClicked')"
		@keydown.enter="clickable && emit('rowClicked')"
	>
		<slot />
	</tr>
</template>
