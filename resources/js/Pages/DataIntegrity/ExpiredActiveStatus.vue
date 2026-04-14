<script setup>
import { Head } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import {
	CheckCircleIcon,
	ClockIcon,
	UserCircleIcon,
	BuildingOfficeIcon,
	UserGroupIcon,
	ChevronDownIcon,
	ChevronRightIcon,
	ExclamationTriangleIcon,
} from "@heroicons/vue/24/outline";

const props = defineProps({
	staff: {
		type: Object,
		required: true,
	},
});

const breadcrumbLinks = [
	{
		name: "Data Integrity",
		href: route("data-integrity.index"),
	},
	{
		name: "Expired Active Status",
	},
];

// Calculate total count from grouped data
const totalCount = computed(() => {
	let count = 0;
	Object.values(props.staff).forEach((departmentGroup) => {
		Object.values(departmentGroup).forEach((unitGroup) => {
			count += unitGroup.length;
		});
	});
	return count;
});

const isEmpty = computed(() => totalCount.value === 0);

// Summary statistics
const summaryStats = computed(() => {
	const departments = Object.keys(props.staff).length;
	let totalUnits = 0;

	const departmentBreakdown = Object.entries(props.staff).map(
		([deptName, deptUnits]) => {
			const units = Object.keys(deptUnits).length;
			totalUnits += units;

			const staffCount = Object.values(deptUnits).reduce(
				(sum, unitGroup) => sum + unitGroup.length,
				0,
			);

			return {
				name: deptName,
				units,
				staff: staffCount,
			};
		},
	);

	// Sort by staff count descending
	departmentBreakdown.sort((a, b) => b.staff - a.staff);

	return {
		departments,
		units: totalUnits,
		departmentBreakdown,
	};
});

// Collapsible state management
const collapsedDepartments = ref({});
const collapsedUnits = ref({});

const toggleDepartment = (departmentName) => {
	collapsedDepartments.value[departmentName] =
		!collapsedDepartments.value[departmentName];
};

const toggleUnit = (departmentName, unitName) => {
	const key = `${departmentName}::${unitName}`;
	collapsedUnits.value[key] = !collapsedUnits.value[key];
};

const isDepartmentCollapsed = (departmentName) => {
	return collapsedDepartments.value[departmentName] || false;
};

const isUnitCollapsed = (departmentName, unitName) => {
	const key = `${departmentName}::${unitName}`;
	return collapsedUnits.value[key] || false;
};
</script>

<template>
	<MainLayout>
		<Head title="Active Staff with Expired Status" />
		<main class="max-w-7xl mx-auto sm:px-6 lg:px-8">
			<BreadCrumpVue :links="breadcrumbLinks" />
			<div class="overflow-hidden shadow-sm sm:rounded-lg px-6">
				<div class="py-6">
					<!-- Header -->
					<div class="mb-6">
						<h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
							Active Staff with Expired Status
						</h1>
						<p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
							{{ totalCount }} staff
							{{ totalCount === 1 ? "member has" : "members have" }}
							an active status with an end date that is today or in the past
						</p>
					</div>

					<!-- Summary Statistics -->
					<div
						v-if="!isEmpty"
						class="mb-8 grid grid-cols-1 lg:grid-cols-3 gap-4"
					>
						<!-- Total Staff Card -->
						<div
							class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4 border-2 border-yellow-200 dark:border-yellow-800"
						>
							<div class="flex items-center justify-between">
								<div>
									<p
										class="text-sm font-medium text-yellow-700 dark:text-yellow-300"
									>
										Total Staff
									</p>
									<p
										class="mt-1 text-3xl font-bold text-yellow-900 dark:text-yellow-100"
									>
										{{ totalCount }}
									</p>
								</div>
								<ClockIcon
									class="h-12 w-12 text-yellow-400 dark:text-yellow-600"
								/>
							</div>
						</div>

						<!-- Departments Card -->
						<div
							class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border-2 border-blue-200 dark:border-blue-800"
						>
							<div class="flex items-center justify-between">
								<div>
									<p
										class="text-sm font-medium text-blue-700 dark:text-blue-300"
									>
										Departments
									</p>
									<p
										class="mt-1 text-3xl font-bold text-blue-900 dark:text-blue-100"
									>
										{{ summaryStats.departments }}
									</p>
								</div>
								<BuildingOfficeIcon
									class="h-12 w-12 text-blue-400 dark:text-blue-600"
								/>
							</div>
						</div>

						<!-- Units Card -->
						<div
							class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4 border-2 border-purple-200 dark:border-purple-800"
						>
							<div class="flex items-center justify-between">
								<div>
									<p
										class="text-sm font-medium text-purple-700 dark:text-purple-300"
									>
										Units
									</p>
									<p
										class="mt-1 text-3xl font-bold text-purple-900 dark:text-purple-100"
									>
										{{ summaryStats.units }}
									</p>
								</div>
								<UserGroupIcon
									class="h-12 w-12 text-purple-400 dark:text-purple-600"
								/>
							</div>
						</div>
					</div>

					<!-- Department Breakdown Table -->
					<div
						v-if="!isEmpty"
						class="mb-8 bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden mt-5"
					>
						<div
							class="px-4 py-3 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600"
						>
							<h3
								class="text-lg font-semibold text-gray-900 dark:text-gray-100"
							>
								Department Summary
							</h3>
						</div>
						<div class="overflow-x-auto">
							<table
								class="min-w-full divide-y divide-gray-200 dark:divide-gray-600"
							>
								<thead class="bg-gray-50 dark:bg-gray-700">
									<tr>
										<th
											scope="col"
											class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
										>
											Department
										</th>
										<th
											scope="col"
											class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
										>
											Units
										</th>
										<th
											scope="col"
											class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
										>
											Staff with Expired Status
										</th>
										<th
											scope="col"
											class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
										>
											Percentage
										</th>
									</tr>
								</thead>
								<tbody
									class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-600"
								>
									<tr
										v-for="dept in summaryStats.departmentBreakdown"
										:key="dept.name"
										class="hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors"
									>
										<td
											class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100"
										>
											{{ dept.name }}
										</td>
										<td
											class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400"
										>
											{{ dept.units }}
										</td>
										<td
											class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100"
										>
											<span
												class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-200"
											>
												{{ dept.staff }}
											</span>
										</td>
										<td
											class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400 text-right"
										>
											{{ ((dept.staff / totalCount) * 100).toFixed(1) }}%
										</td>
									</tr>
								</tbody>
							</table>
						</div>
					</div>

					<!-- No Issues State -->
					<div
						v-if="isEmpty"
						class="rounded-lg bg-green-50 dark:bg-green-900/20 p-8 text-center"
					>
						<CheckCircleIcon
							class="mx-auto h-12 w-12 text-green-600 dark:text-green-400"
						/>
						<h3
							class="mt-4 text-lg font-semibold text-green-900 dark:text-green-100"
						>
							No Issues Found
						</h3>
						<p class="mt-2 text-sm text-green-700 dark:text-green-300">
							All staff with active status have valid status end dates.
						</p>
					</div>

					<!-- Grouped Staff List -->
					<div v-else class="space-y-8">
						<!-- Department Groups -->
						<div
							v-for="(departmentGroup, departmentName) in staff"
							:key="departmentName"
							class="space-y-4"
						>
							<!-- Department Header (Clickable) -->
							<button
								type="button"
								class="w-full flex items-center gap-3 pb-2 border-b-2 border-yellow-300 dark:border-yellow-700 hover:bg-yellow-50 dark:hover:bg-yellow-900/10 transition-colors cursor-pointer"
								@click="toggleDepartment(departmentName)"
							>
								<component
									:is="
										isDepartmentCollapsed(departmentName)
											? ChevronRightIcon
											: ChevronDownIcon
									"
									class="h-5 w-5 text-yellow-600 dark:text-yellow-400 flex-shrink-0"
								/>
								<BuildingOfficeIcon
									class="h-6 w-6 text-yellow-600 dark:text-yellow-400 flex-shrink-0"
								/>
								<h2
									class="text-xl font-bold text-yellow-900 dark:text-yellow-100 text-left"
								>
									{{ departmentName }}
								</h2>
								<span
									class="ml-auto px-3 py-1 text-sm font-semibold rounded-full bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-200"
								>
									{{
										Object.values(departmentGroup).reduce(
											(sum, units) => sum + units.length,
											0,
										)
									}}
									staff
								</span>
							</button>

							<!-- Unit Groups within Department -->
							<div
								v-if="!isDepartmentCollapsed(departmentName)"
								class="ml-6 space-y-6"
							>
								<div
									v-for="(unitGroup, unitName) in departmentGroup"
									:key="unitName"
									class="space-y-3"
								>
									<!-- Unit Header (Clickable) -->
									<button
										type="button"
										class="w-full flex items-center gap-2 pb-1 border-b border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors cursor-pointer"
										@click="toggleUnit(departmentName, unitName)"
									>
										<component
											:is="
												isUnitCollapsed(departmentName, unitName)
													? ChevronRightIcon
													: ChevronDownIcon
											"
											class="h-4 w-4 text-gray-600 dark:text-gray-400 flex-shrink-0"
										/>
										<UserGroupIcon
											class="h-5 w-5 text-gray-600 dark:text-gray-400 flex-shrink-0"
										/>
										<h3
											class="text-lg font-semibold text-gray-900 dark:text-gray-100 text-left"
										>
											{{ unitName }}
										</h3>
										<span
											class="ml-auto px-2 py-0.5 text-xs font-medium rounded-full bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300"
										>
											{{ unitGroup.length }}
											{{ unitGroup.length === 1 ? "staff" : "staff" }}
										</span>
									</button>

									<!-- Staff Members in Unit -->
									<div
										v-if="!isUnitCollapsed(departmentName, unitName)"
										class="ml-6 space-y-2"
									>
										<div
											v-for="member in unitGroup"
											:key="member.id"
											class="rounded-lg border border-yellow-200 dark:border-yellow-800 bg-white dark:bg-gray-800 p-4 hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors"
										>
											<div class="flex items-start gap-4">
												<div
													class="flex-shrink-0 h-12 w-12 rounded-full bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center"
												>
													<ExclamationTriangleIcon
														class="h-7 w-7 text-yellow-600 dark:text-yellow-400"
													/>
												</div>
												<div class="flex-1">
													<h4
														class="text-lg font-semibold text-gray-900 dark:text-gray-100"
													>
														{{ member.name }}
													</h4>
													<div
														class="mt-1 flex flex-wrap gap-x-4 text-sm text-gray-500 dark:text-gray-400"
													>
														<span>Staff #{{ member.staff_number }}</span>
														<span v-if="member.file_number">
															File #{{ member.file_number }}
														</span>
														<span v-if="member.current_rank">
															{{ member.current_rank }}
														</span>
													</div>

													<!-- Expired Status Alert -->
													<div
														class="mt-3 inline-flex items-center gap-2 px-3 py-1.5 rounded-md bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300"
													>
														<ClockIcon class="h-4 w-4" />
														<span class="text-sm font-medium">
															Status ended:
															{{ member.status_end_date_formatted }}
														</span>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>

					<div
						v-if="!isEmpty"
						class="mt-6 rounded-md bg-blue-50 dark:bg-blue-900/20 p-4"
					>
						<p class="text-sm text-blue-700 dark:text-blue-300">
							<strong>Note:</strong> These staff members have an active status
							record but the status end date has already passed. This requires
							manual review to either extend the active status, create a new
							active status record, or update the staff to a different status
							(e.g., separated, retired).
						</p>
					</div>
				</div>
			</div>
		</main>
	</MainLayout>
</template>
