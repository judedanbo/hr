<script setup>
const emit = defineEmits(["rowClicked"]);

const props = defineProps({
	// Most rows in the app are not clickable, so this defaults to false.
	// Rows that navigate on click opt in, which also gives them keyboard access.
	clickable: {
		type: Boolean,
		default: false,
	},
});

/**
 * Activate the row on Enter by dispatching a real click rather than emitting.
 *
 * Most call sites attach a native @click, which falls through to this <tr>
 * because `click` is not a declared emit. Emitting `rowClicked` here would
 * therefore do nothing for them. Dispatching a click fires both the
 * fallthrough listener and the emit below.
 *
 * Only handled when the row itself has focus — an Enter press on a button
 * inside the row bubbles up here, and must be left alone.
 */
const activateOnEnter = (event) => {
	if (!props.clickable || event.target !== event.currentTarget) {
		return;
	}

	event.preventDefault();
	event.currentTarget.click();
};
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
		@keydown.enter="activateOnEnter"
	>
		<slot />
	</tr>
</template>
