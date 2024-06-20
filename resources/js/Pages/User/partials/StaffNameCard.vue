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
	<section class="flex items-center">
		<Avatar :image="staff.image" :initials="staff.initials" />
		<div class="ml-4">
			<div class="text-green-900 dark:text-gray-50 font-semibold">
				{{ staff.name }}
			</div>
			<div class="text-xs text-gray-500 dark:text-gray-200">
				{{ staff.gender }}
				&bull;
				{{ staff.dob }}
				({{ staff.dob_distance }})
			</div>
		</div>
	</section>
</template>
