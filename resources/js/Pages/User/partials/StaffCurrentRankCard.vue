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
	<section v-if="staff.current_rank">
		<div class="font-semibold text-green-900 dark:text-gray-200">
			{{ staff.current_rank.name }}
		</div>
		<p class="text-xs text-gray-500 dark:text-gray-200">
			{{ staff.current_rank.start_date_distance }}
		</p>

		<p
			v-if="staff.current_rank.remarks"
			class="text-green-800 dark:text-gray-200 text-xs tracking-wider"
		>
			{{ staff.current_rank.remarks }}
		</p>
	</section>
</template>
