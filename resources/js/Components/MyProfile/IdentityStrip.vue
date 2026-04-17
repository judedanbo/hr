<script setup>
import ProfileProgress from "@/Components/MyProfile/ProfileProgress.vue";
import { computed } from "vue";

const props = defineProps({
	person: { type: Object, required: true },
	staff: { type: Object, required: true },
	qualifications: { type: Array, default: () => [] },
	contacts: { type: Array, default: () => null },
	address: { type: Object, default: () => null },
});

const currentRank = computed(
	() => props.staff.ranks?.find((r) => !r.end_date) ?? props.staff.ranks?.[0],
);
const currentDepartment = computed(() => {
	const unit =
		props.staff.units?.find((u) => !u.end_date) ?? props.staff.units?.[0];
	return unit?.department ?? unit?.unit_name ?? null;
});
</script>

<template>
	<div
		class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl p-5 sm:p-6 shadow-sm flex flex-col sm:flex-row items-center sm:items-stretch gap-4 sm:gap-5"
	>
		<div class="flex-shrink-0">
			<img
				v-if="person.image"
				:src="person.image"
				alt=""
				class="w-[72px] h-[72px] rounded-full object-cover border-2 border-white dark:border-gray-700 shadow"
			/>
			<div
				v-else
				class="w-[72px] h-[72px] rounded-full bg-gradient-to-br from-gray-400 to-gray-600 dark:from-gray-500 dark:to-gray-700 flex items-center justify-center text-white font-bold text-2xl"
			>
				{{ person.initials }}
			</div>
		</div>
		<div class="flex-1 min-w-0 text-center sm:text-left">
			<h1
				class="text-xl sm:text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-50"
			>
				{{ person.name }}
			</h1>
			<p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
				<span v-if="currentRank">{{ currentRank.name }}</span>
				<span v-if="currentRank && currentDepartment"> · </span>
				<span v-if="currentDepartment">{{ currentDepartment }}</span>
				<span v-if="staff.staff_number">
					· Staff #{{ staff.staff_number }}</span
				>
			</p>
		</div>
		<div class="flex items-center justify-center sm:justify-end">
			<ProfileProgress
				:person="person"
				:qualifications="qualifications"
				:contacts="contacts"
				:address="address"
			/>
		</div>
	</div>
</template>
