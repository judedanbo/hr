<script setup>
defineProps({
	unit: { type: Object, required: true },
});
</script>
<template>
	<section class="flex items-center">
		<div
			class="flex-shrink-0 w-16 h-16 font-bold text-white dark:text-gray-600 bg-gray-400 dark:bg-gray-200 rounded-full flex justify-center items-center"
		>
			{{ unit.short_name }}
		</div>

		<div class="ml-4">
			<div class="text-green-900 dark:text-gray-50 font-semibold">
				{{ unit.name }}
			</div>
			<div class="text-sm text-gray-500 dark:text-gray-100"></div>
		</div>
	</section>
</template>
