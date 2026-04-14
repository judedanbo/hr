<script setup>
import { computed } from "vue";
import {
	UsersIcon,
	UserGroupIcon,
	Square3Stack3DIcon,
} from "@heroicons/vue/24/outline";

const props = defineProps({
	unit: {
		type: Object,
		required: true,
	},
});

// Calculate female staff (total - male)
const femaleStaff = computed(() => {
	const total = props.unit?.staff_number || 0;
	const male = props.unit?.male_staff || 0;
	return total - male;
});

// Stats configuration
const stats = computed(() => [
	{
		id: "total-staff",
		name: "Total Staff",
		value: props.unit?.staff_number || 0,
		icon: UsersIcon,
		color: "bg-green-600 dark:bg-green-700",
	},
	{
		id: "male-staff",
		name: "Male Staff",
		value: props.unit?.male_staff || 0,
		icon: UserGroupIcon,
		color: "bg-blue-600 dark:bg-blue-700",
	},
	{
		id: "female-staff",
		name: "Female Staff",
		value: femaleStaff.value,
		icon: UserGroupIcon,
		color: "bg-pink-600 dark:bg-pink-700",
	},
	{
		id: "sub-units",
		name: "Sub-Units",
		value: props.unit?.subs_number || 0,
		icon: Square3Stack3DIcon,
		color: "bg-purple-600 dark:bg-purple-700",
	},
]);
</script>

<template>
	<section>
		<h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
			Overview
		</h2>
		<dl class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
			<div
				v-for="stat in stats"
				:key="stat.id"
				class="relative overflow-hidden rounded-lg bg-white dark:bg-gray-800 px-4 py-5 shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700 sm:px-6"
			>
				<dt>
					<div class="absolute rounded-md p-3" :class="stat.color">
						<component
							:is="stat.icon"
							class="h-6 w-6 text-white"
							aria-hidden="true"
						/>
					</div>
					<p
						class="ml-16 truncate text-sm font-medium text-gray-500 dark:text-gray-400"
					>
						{{ stat.name }}
					</p>
				</dt>
				<dd class="ml-16 flex items-baseline">
					<p
						class="text-2xl font-semibold text-gray-900 dark:text-white"
					>
						{{ stat.value?.toLocaleString() ?? "0" }}
					</p>
				</dd>
			</div>
		</dl>
	</section>
</template>
