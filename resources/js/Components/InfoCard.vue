<script setup>
defineProps({
	title: String,
	value: { type: Number, default: null },
	// Retained because every call site passes it; the card has never been
	// clickable and the handler it once had was dead code.
	link: String,
});
</script>
<template>
	<div class="flex flex-col gap-y-1">
		<dt class="text-sm text-gray-500 dark:text-gray-400">
			{{ title }}
		</dt>
		<dd
			class="order-first text-2xl font-semibold tabular-nums tracking-tight text-gray-900 dark:text-gray-50"
		>
			{{ value?.toLocaleString() ?? 0 }}
		</dd>
	</div>
</template>
