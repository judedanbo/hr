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
	<section>
		<div class="font-semibold text-green-900 dark:text-gray-200">
			{{ staff.retirement_date }}
		</div>
		<p class="text-xs text-gray-500 dark:text-gray-200">
			{{ staff.retirement_date_distance }}
		</p>
	</section>
</template>
