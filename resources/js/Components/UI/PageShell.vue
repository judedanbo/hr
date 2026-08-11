<script setup>
defineProps({
	// 'default' constrains to max-w-7xl; 'full' lets dashboards use the width.
	width: {
		type: String,
		default: "default",
		validator: (value) => ["default", "full"].includes(value),
	},
});
</script>
<template>
	<div
		:class="[
			width === 'full' ? 'w-full' : 'mx-auto w-full max-w-7xl',
			'px-4 sm:px-6 lg:px-8 py-6 space-y-6',
		]"
	>
		<slot />
	</div>
</template>
