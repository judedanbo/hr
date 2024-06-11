<script setup>
defineProps({
	item: {
		type: Object,
		required: true,
	},
});
</script>
<template>
	<div class="">
		<dt>
			<span class="sr-only">{{ item.title }}</span>
			<span class="text-xs leading-6 text-gray-500 dark:text-gray-50">
				{{ item.title }}
			</span>
			<!-- <CalendarDaysIcon
							class="h-6 w-5 text-gray-400 dark:text-gray-50"
							aria-hidden="true"
						/> -->
		</dt>
		<dd class="leading-6 text-gray-500 dark:text-gray-50">
			<span> {{ item.value.join(" | ") }}</span>
		</dd>
	</div>
</template>
