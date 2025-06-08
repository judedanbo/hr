<script setup>
const emit = defineEmits(["openOffice"]);
defineProps({
	office: { type: Object, required: true },
});
</script>
<template>
	<section class="flex items-center">
		<div
			class="flex-shrink-0 w-14 h-14 text-white dark:text-gray-500 bg-gray-400 dark:bg-gray-50 font-bold tracking-wider rounded-full flex justify-center items-center"
		>
			{{ office.short_name }}
		</div>

		<div class="ml-4 text-xl">
			{{ office.name }}
			<div
				v-if="office.district"
				class="text-sm text-gray-800 dark:text-gray-200"
			>
				{{ office.district }}
				<div v-if="office.region">
					{{ office.region }}
				</div>
			</div>
		</div>
	</section>
	<!-- <JobName :job="job" /> -->
</template>
