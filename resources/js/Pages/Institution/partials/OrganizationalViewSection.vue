<script setup>
import { Link } from "@inertiajs/vue3";
import {
	BuildingOffice2Icon,
	UsersIcon,
	Square3Stack3DIcon,
} from "@heroicons/vue/24/outline";

const props = defineProps({
	departments: {
		type: Array,
		required: true,
	},
	institutionId: {
		type: Number,
		required: true,
	},
});

const emit = defineEmits(["department-click"]);

function handleDepartmentClick(department) {
	emit("department-click", {
		filter: "department",
		params: { id: department.id },
		title: `${department.name} Staff`,
	});
}
</script>

<template>
	<section>
		<div class="flex items-center justify-between mb-4">
			<h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
				Organizational Structure
			</h2>
			<Link
				:href="route('institution.show', institutionId)"
				class="text-sm text-green-600 hover:text-green-500 dark:text-green-400"
			>
				View all units
			</Link>
		</div>

		<div
			v-if="departments.length === 0"
			class="text-center py-8 bg-white dark:bg-gray-800 rounded-lg"
		>
			<BuildingOffice2Icon
				class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500"
			/>
			<p class="mt-2 text-gray-500 dark:text-gray-400">
				No departments found.
			</p>
		</div>

		<div
			v-else
			class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3"
		>
			<div
				v-for="dept in departments"
				:key="dept.id"
				class="bg-white dark:bg-gray-800 rounded-lg shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700 p-4 hover:shadow-md transition-shadow cursor-pointer"
				@click="handleDepartmentClick(dept)"
			>
				<div class="flex items-start justify-between">
					<div class="flex items-center gap-3">
						<div
							class="rounded-lg bg-purple-100 dark:bg-purple-900/30 p-2"
						>
							<BuildingOffice2Icon
								class="h-5 w-5 text-purple-600 dark:text-purple-400"
							/>
						</div>
						<div>
							<h3
								class="text-sm font-semibold text-gray-900 dark:text-gray-100"
							>
								{{ dept.name }}
							</h3>
							<p
								v-if="dept.short_name"
								class="text-xs text-gray-500 dark:text-gray-400"
							>
								{{ dept.short_name }}
							</p>
						</div>
					</div>
				</div>

				<dl class="mt-4 grid grid-cols-3 gap-2 text-center">
					<div
						class="rounded-lg bg-gray-50 dark:bg-gray-700/50 px-2 py-2"
					>
						<dt
							class="text-xs text-gray-500 dark:text-gray-400"
						>
							Divisions
						</dt>
						<dd
							class="text-lg font-semibold text-gray-900 dark:text-gray-100"
						>
							{{ dept.divisions_count?.toLocaleString() || 0 }}
						</dd>
					</div>
					<div
						class="rounded-lg bg-gray-50 dark:bg-gray-700/50 px-2 py-2"
					>
						<dt
							class="text-xs text-gray-500 dark:text-gray-400"
						>
							Units
						</dt>
						<dd
							class="text-lg font-semibold text-gray-900 dark:text-gray-100"
						>
							{{ dept.units_count?.toLocaleString() || 0 }}
						</dd>
					</div>
					<div
						class="rounded-lg bg-gray-50 dark:bg-gray-700/50 px-2 py-2"
					>
						<dt
							class="text-xs text-gray-500 dark:text-gray-400"
						>
							Staff
						</dt>
						<dd
							class="text-lg font-semibold text-gray-900 dark:text-gray-100"
						>
							{{ dept.staff_count?.toLocaleString() || 0 }}
						</dd>
					</div>
				</dl>

				<!-- Gender breakdown -->
				<div
					v-if="dept.staff_count > 0"
					class="mt-3 flex items-center gap-2"
				>
					<div class="flex-1 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
						<div
							class="h-full bg-blue-500"
							:style="{
								width: `${(dept.male_count / dept.staff_count) * 100}%`,
							}"
						></div>
					</div>
					<span class="text-xs text-gray-500 dark:text-gray-400 w-20 text-right">
						{{ dept.male_count }}M / {{ dept.female_count }}F
					</span>
				</div>
			</div>
		</div>
	</section>
</template>
