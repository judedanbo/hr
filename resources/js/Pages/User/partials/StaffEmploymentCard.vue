<script setup>
import Avatar from "@/Pages/Person/partials/Avatar.vue";
defineProps({
	staff: {
		type: Object,
		required: true,
	},
});
</script>
<template>
	<section class="flex flex-col">
		<div class="text-green-800 dark:text-gray-200">
			{{ staff.staff_number }}

			{{ staff.file_number ? " | " + staff.file_number : "" }}
		</div>
		<div class="text-xs text-gray-900 dark:text-gray-200">
			{{ staff.hire_date }}
			&bull;
			{{ staff.hire_date_distance }}
		</div>
	</section>
</template>
