<script setup>
import { Head } from "@inertiajs/vue3";
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import {
	ExclamationTriangleIcon,
	CheckCircleIcon,
	BuildingOfficeIcon,
} from "@heroicons/vue/24/outline";

const props = defineProps({
	staff: {
		type: Array,
		required: true,
	},
});

const breadcrumbLinks = [
	{
		name: "Data Integrity",
		href: route("data-integrity.index"),
	},
	{
		name: "Multiple Unit Assignments",
	},
];
</script>

<template>
	<MainLayout>
		<Head title="Staff with Multiple Unit Assignments" />
		<main class="max-w-7xl mx-auto sm:px-6 lg:px-8">
			<BreadCrumpVue :links="breadcrumbLinks" />
			<div class="overflow-hidden shadow-sm sm:rounded-lg px-6">
				<div class="py-6">
					<!-- Header -->
					<div class="mb-6">
						<h1
							class="text-3xl font-bold text-gray-900 dark:text-gray-100"
						>
							Staff with Multiple Unit Assignments
						</h1>
						<p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
							{{ staff.length }} staff
							{{
								staff.length === 1 ? "member is" : "members are"
							}}
							assigned to multiple units simultaneously
						</p>
					</div>

					<!-- No Issues State -->
					<div
						v-if="staff.length === 0"
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
							All staff members have single unit assignments.
						</p>
					</div>

					<!-- Staff List -->
					<div v-else class="space-y-6">
						<div
							v-for="member in staff"
							:key="member.id"
							class="rounded-lg border-2 border-yellow-200 dark:border-yellow-800 bg-yellow-50 dark:bg-yellow-900/20 p-6"
						>
							<div class="flex items-start gap-4">
								<div class="flex-1">
									<div class="flex items-start gap-3">
										<ExclamationTriangleIcon
											class="h-6 w-6 flex-shrink-0 text-yellow-600 dark:text-yellow-400"
										/>
										<div class="flex-1">
											<h3
												class="text-lg font-semibold text-yellow-900 dark:text-yellow-100"
											>
												{{ member.name }}
											</h3>
											<p
												class="text-sm text-yellow-700 dark:text-yellow-300"
											>
												Staff #{{ member.staff_number }}
												<span v-if="member.file_number">
													| File #{{ member.file_number }}
												</span>
											</p>
											<p
												class="mt-1 text-sm font-medium text-yellow-800 dark:text-yellow-200"
											>
												{{ member.active_units_count }} active unit
												assignments found
											</p>
										</div>
									</div>

									<!-- Units List -->
									<div class="mt-4 space-y-2 pl-9">
										<div
											v-for="(unit, index) in member.units"
											:key="unit.pivot_id"
											class="rounded-md bg-white dark:bg-gray-800 p-3 shadow-sm"
										>
											<div
												class="flex items-center justify-between"
											>
												<div class="flex items-center gap-3">
													<BuildingOfficeIcon
														class="h-5 w-5 text-gray-400"
													/>
													<div>
														<p
															class="font-medium text-gray-900 dark:text-gray-100"
														>
															{{ unit.name }}
															<span
																v-if="index === 0"
																class="ml-2 inline-flex items-center rounded-full bg-blue-100 dark:bg-blue-900 px-2.5 py-0.5 text-xs font-medium text-blue-800 dark:text-blue-200"
															>
																Most Recent
															</span>
														</p>
														<p
															class="text-sm text-gray-500 dark:text-gray-400"
														>
															<span
																v-if="unit.type"
																class="capitalize"
															>
																{{ unit.type }} •
															</span>
															Started:
															{{ unit.start_date_formatted || "N/A" }}
														</p>
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
						v-if="staff.length > 0"
						class="mt-6 rounded-md bg-blue-50 dark:bg-blue-900/20 p-4"
					>
						<p class="text-sm text-blue-700 dark:text-blue-300">
							<strong>Note:</strong> Staff with multiple unit
							assignments may indicate a data entry error or a
							legitimate secondary assignment. Review each case and
							manually update unit assignments through the staff
							management interface if needed.
						</p>
					</div>
				</div>
			</div>
		</main>
	</MainLayout>
</template>
