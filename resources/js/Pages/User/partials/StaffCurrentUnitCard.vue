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
	<div v-if="staff.current_unit">
		<div class="font-semibold text-green-800 dark:text-gray-50">
			{{ staff.current_unit.name }}
		</div>
		<div class="text-xs text-gray-500 dark:text-gray-200">
			{{ staff.current_unit.start_date }}
		</div>
		<div class="text-xs text-gray-500 dark:text-gray-200">
			{{ staff.current_unit.start_date_distance }}
		</div>
	</div>
</template>
