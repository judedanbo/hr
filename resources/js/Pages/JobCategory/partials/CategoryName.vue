<script setup>
const emit = defineEmits(["openCategory"]);
defineProps({
	category: { type: Object, required: true },
});
</script>
<template>
	<section class="flex items-center">
		<div
			class="flex-shrink-0 w-14 h-14 text-white dark:text-gray-500 bg-gray-400 dark:bg-gray-50 font-bold tracking-wider rounded-full flex justify-center items-center"
		>
			{{ category.short_name }}
		</div>

		<div class="ml-4">
			{{ category.name }}
			{{ category.short_name ? "(" + category.short_name + ")" : "" }}
			<div class="text-sm text-gray-500"></div>
		</div>
	</section>
	<!-- <JobName :job="job" /> -->
</template>
