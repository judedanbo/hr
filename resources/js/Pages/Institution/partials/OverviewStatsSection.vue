<script setup>
import { computed } from "vue";
import {
	UserGroupIcon,
	UsersIcon,
	BuildingOffice2Icon,
	BuildingOfficeIcon,
	Square3Stack3DIcon,
	CalendarDaysIcon,
	ClockIcon,
	UserPlusIcon,
} from "@heroicons/vue/24/outline";
import { ArrowUpIcon, ArrowDownIcon } from "@heroicons/vue/20/solid";

const props = defineProps({
	overview: {
		type: Object,
		required: true,
	},
});

const emit = defineEmits(["stat-click"]);

const stats = computed(() => [
	{
		id: "active-staff",
		name: "Active Staff",
		value: props.overview.active_staff,
		icon: UsersIcon,
		filter: "active",
		color: "bg-green-600 dark:bg-green-700",
	},
	{
		id: "male-staff",
		name: "Male Staff",
		value: props.overview.male_count,
		icon: UserGroupIcon,
		filter: "gender",
		filterParams: { value: "M" },
		color: "bg-blue-600 dark:bg-blue-700",
	},
	{
		id: "female-staff",
		name: "Female Staff",
		value: props.overview.female_count,
		icon: UserGroupIcon,
		filter: "gender",
		filterParams: { value: "F" },
		color: "bg-pink-600 dark:bg-pink-700",
	},
	{
		id: "new-hires",
		name: "New Hires (This Year)",
		value: props.overview.new_hires_this_year,
		icon: UserPlusIcon,
		change: calculateChange(
			props.overview.new_hires_this_year,
			props.overview.new_hires_last_year,
		),
		color: "bg-emerald-600 dark:bg-emerald-700",
	},
	{
		id: "departments",
		name: "Departments",
		value: props.overview.departments_count,
		icon: BuildingOffice2Icon,
		clickable: false,
		color: "bg-purple-600 dark:bg-purple-700",
	},
	{
		id: "divisions",
		name: "Divisions",
		value: props.overview.divisions_count,
		icon: BuildingOfficeIcon,
		clickable: false,
		color: "bg-indigo-600 dark:bg-indigo-700",
	},
	{
		id: "units",
		name: "Units",
		value: props.overview.units_count,
		icon: Square3Stack3DIcon,
		clickable: false,
		color: "bg-cyan-600 dark:bg-cyan-700",
	},
	{
		id: "avg-tenure",
		name: "Avg. Tenure (Years)",
		value: props.overview.avg_tenure_years,
		icon: ClockIcon,
		clickable: false,
		color: "bg-amber-600 dark:bg-amber-700",
	},
]);

function calculateChange(current, previous) {
	if (!previous || previous === 0) return null;
	const change = ((current - previous) / previous) * 100;
	return {
		value: Math.abs(change).toFixed(1) + "%",
		type: change >= 0 ? "increase" : "decrease",
	};
}

function handleClick(stat) {
	if (stat.clickable === false) return;
	emit("stat-click", {
		filter: stat.filter,
		params: stat.filterParams || {},
		title: stat.name,
	});
}
</script>

<template>
	<section>
		<h2
			class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4"
		>
			Overview
		</h2>
		<dl
			class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4"
		>
			<div
				v-for="stat in stats"
				:key="stat.id"
				class="relative overflow-hidden rounded-lg bg-white dark:bg-gray-800 px-4 py-5 shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700 sm:px-6 transition-all duration-200"
				:class="[
					stat.clickable !== false
						? 'cursor-pointer hover:shadow-md hover:ring-green-500 dark:hover:ring-green-400'
						: '',
				]"
				@click="handleClick(stat)"
			>
				<dt>
					<div
						class="absolute rounded-md p-3"
						:class="stat.color"
					>
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
					<p
						v-if="stat.change"
						:class="[
							stat.change.type === 'increase'
								? 'text-green-600 dark:text-green-400'
								: 'text-red-600 dark:text-red-400',
							'ml-2 flex items-baseline text-sm font-semibold',
						]"
					>
						<ArrowUpIcon
							v-if="stat.change.type === 'increase'"
							class="h-4 w-4 flex-shrink-0 self-center text-green-500"
							aria-hidden="true"
						/>
						<ArrowDownIcon
							v-else
							class="h-4 w-4 flex-shrink-0 self-center text-red-500"
							aria-hidden="true"
						/>
						{{ stat.change.value }}
					</p>
				</dd>
			</div>
		</dl>
	</section>
</template>
