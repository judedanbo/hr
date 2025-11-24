<script setup>
import { Head } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import {
	CheckCircleIcon,
	PhotoIcon,
	UserCircleIcon,
	BuildingOfficeIcon,
	UserGroupIcon,
	ChevronDownIcon,
	ChevronRightIcon,
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
		name: "Staff without Pictures",
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
		<Head title="Staff without Profile Pictures" />
		<main class="max-w-7xl mx-auto sm:px-6 lg:px-8">
			<BreadCrumpVue :links="breadcrumbLinks" />
			<div class="overflow-hidden shadow-sm sm:rounded-lg px-6">
				<div class="py-6">
					<!-- Header -->
					<div class="mb-6">
						<h1
							class="text-3xl font-bold text-gray-900 dark:text-gray-100"
						>
							Staff without Profile Pictures
						</h1>
						<p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
							{{ totalCount }} active staff
							{{ totalCount === 1 ? "member has" : "members have" }}
							no profile picture uploaded
						</p>
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
							All active staff members have profile pictures.
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
									{{
										Object.values(departmentGroup).reduce(
											(sum, units) => sum + units.length,
											0,
										) === 1
											? "staff"
											: "staff"
									}}
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
											<div class="flex items-center gap-4">
												<div
													class="flex-shrink-0 h-16 w-16 rounded-full bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center relative overflow-hidden"
												>
													<UserCircleIcon
														class="h-12 w-12 text-yellow-600 dark:text-yellow-400"
													/>
													<div
														class="absolute inset-0 flex items-center justify-center bg-black/50"
													>
														<PhotoIcon class="h-6 w-6 text-white" />
													</div>
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
														<span
															>Staff #{{ member.staff_number }}</span
														>
														<span v-if="member.file_number">
															File #{{ member.file_number }}
														</span>
														<span v-if="member.hire_date_formatted">
															Hired: {{ member.hire_date_formatted }}
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
							<strong>Note:</strong> Profile pictures need to be
							uploaded manually through the staff profile management
							interface. This cannot be automatically fixed.
						</p>
					</div>
				</div>
			</div>
		</main>
	</MainLayout>
</template>
