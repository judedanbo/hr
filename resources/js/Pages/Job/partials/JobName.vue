<script setup>
defineProps({ job: { type: Object, required: true } });
</script>
<template>
	<div class="flex items-center">
		<div
			class="flex-shrink-0 w-14 h-14 bg-gray-200 rounded-full flex justify-center items-center"
		>
			{{ job.short_name }}
		</div>

		<div class="ml-4">
			<section>
				{{ job.name }}
				{{ job.short_name ? "(" + job.short_name + ")" : "" }}
			</section>
			<div class="text-sm text-gray-500"></div>
		</div>
	</div>
</template>
