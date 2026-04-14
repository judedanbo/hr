<script setup>
import { Head } from "@inertiajs/vue3";
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import {
	CheckCircleIcon,
	ExclamationTriangleIcon,
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
		url: route("data-integrity.index"),
	},
	{
		name: "Separated but Active",
	},
];
</script>

<template>
	<MainLayout>
		<Head title="Separated but Active" />
		<main class="max-w-7xl mx-auto sm:px-6 lg:px-8">
			<BreadCrumpVue :links="breadcrumbLinks" />
			<div class="overflow-hidden shadow-sm sm:rounded-lg px-6">
				<div class="py-6">
					<!-- Header -->
					<div class="mb-6">
						<h1
							class="text-3xl font-bold text-gray-900 dark:text-gray-100"
						>
							Separated but Active
						</h1>
						<p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
							{{ staff.length }} staff
							{{
								staff.length === 1 ? "member is" : "members are"
							}}
							marked as active but have separation status recorded
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
							No active staff members have separation status recorded.
						</p>
					</div>

					<!-- Staff List -->
					<div v-else class="space-y-4">
						<div
							v-for="member in staff"
							:key="member.id"
							class="rounded-lg border-2 border-yellow-200 dark:border-yellow-800 bg-yellow-50 dark:bg-yellow-900/20 p-5"
						>
							<div class="flex items-start gap-4">
								<div
									class="flex-shrink-0 h-10 w-10 rounded-full bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center"
								>
									<ExclamationTriangleIcon
										class="h-6 w-6 text-yellow-600 dark:text-yellow-400"
									/>
								</div>
								<div class="flex-1">
									<h3
										class="text-lg font-semibold text-yellow-900 dark:text-yellow-100"
									>
										{{ member.name }}
									</h3>
									<div
										class="mt-1 flex flex-wrap gap-x-4 text-sm text-yellow-700 dark:text-yellow-300"
									>
										<span>Staff #{{ member.staff_number }}</span>
										<span v-if="member.file_number">
											File #{{ member.file_number }}
										</span>
										<span v-if="member.hire_date_formatted">
											Hired: {{ member.hire_date_formatted }}
										</span>
									</div>

									<!-- Separation Details -->
									<div
										v-if="member.separation_statuses && member.separation_statuses.length > 0"
										class="mt-4 space-y-2"
									>
										<p
											class="text-sm font-medium text-yellow-800 dark:text-yellow-200"
										>
											Separation Status:
										</p>
										<div
											v-for="status in member.separation_statuses"
											:key="status.id"
											class="rounded-md bg-white dark:bg-gray-800 p-3 shadow-sm"
										>
											<div class="flex items-center justify-between">
												<div>
													<p
														class="font-medium text-gray-900 dark:text-gray-100"
													>
														{{ status.name }}
													</p>
													<p
														class="text-sm text-gray-600 dark:text-gray-400 mt-1"
													>
														<span v-if="status.pivot.start_date_formatted">
															Effective:
															{{ status.pivot.start_date_formatted }}
														</span>
														<span
															v-if="status.pivot.end_date_formatted"
															class="ml-2"
														>
															→ {{ status.pivot.end_date_formatted }}
														</span>
														<span
															v-else-if="status.pivot.start_date_formatted"
															class="ml-2"
														>
															→ Present
														</span>
													</p>
												</div>
											</div>
										</div>
									</div>

									<!-- Current Status -->
									<div class="mt-3 text-sm text-yellow-700 dark:text-yellow-300">
										<span class="font-medium">Current Status:</span>
										<span
											class="ml-2 px-2 py-1 rounded-md bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300"
										>
											Active
										</span>
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
							<strong>Note:</strong> These discrepancies require manual
							review. Staff marked as active should not have separation
							status, or separated staff should not be marked as active.
							Please review each case individually through the staff
							management interface.
						</p>
					</div>
				</div>
			</div>
		</main>
	</MainLayout>
</template>
