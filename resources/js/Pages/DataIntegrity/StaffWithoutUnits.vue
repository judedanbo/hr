<script setup>
import { Head } from "@inertiajs/vue3";
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import { CheckCircleIcon, UserIcon } from "@heroicons/vue/24/outline";

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
		name: "Staff without Units",
	},
];
</script>

<template>
	<MainLayout>
		<Head title="Staff without Units" />
		<main class="max-w-7xl mx-auto sm:px-6 lg:px-8">
			<BreadCrumpVue :links="breadcrumbLinks" />
			<div class="overflow-hidden shadow-sm sm:rounded-lg px-6">
				<div class="py-6">
					<!-- Header -->
					<div class="mb-6">
						<h1
							class="text-3xl font-bold text-gray-900 dark:text-gray-100"
						>
							Staff without Units
						</h1>
						<p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
							{{ staff.length }} active staff
							{{
								staff.length === 1 ? "member has" : "members have"
							}}
							no current unit assignment
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
							All active staff members have unit assignments.
						</p>
					</div>

					<!-- Staff List -->
					<div v-else class="space-y-3">
						<div
							v-for="member in staff"
							:key="member.id"
							class="rounded-lg border border-red-200 dark:border-red-800 bg-white dark:bg-gray-800 p-4 hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors"
						>
							<div class="flex items-center gap-4">
								<div
									class="flex-shrink-0 h-10 w-10 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center"
								>
									<UserIcon
										class="h-6 w-6 text-red-600 dark:text-red-400"
									/>
								</div>
								<div class="flex-1">
									<h3
										class="text-lg font-semibold text-gray-900 dark:text-gray-100"
									>
										{{ member.name }}
									</h3>
									<div
										class="mt-1 flex flex-wrap gap-x-4 text-sm text-gray-500 dark:text-gray-400"
									>
										<span>Staff #{{ member.staff_number }}</span>
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

					<div
						v-if="staff.length > 0"
						class="mt-6 rounded-md bg-blue-50 dark:bg-blue-900/20 p-4"
					>
						<p class="text-sm text-blue-700 dark:text-blue-300">
							<strong>Note:</strong> These staff members need to be
							manually assigned to units through the staff management
							interface. This cannot be automatically fixed.
						</p>
					</div>
				</div>
			</div>
		</main>
	</MainLayout>
</template>
