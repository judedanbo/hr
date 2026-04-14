<script setup>
import { Head, Link } from "@inertiajs/vue3";
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
		url: route("data-integrity.index"),
	},
	{
		name: "Staff without Gender",
	},
];
</script>

<template>
	<MainLayout>
		<Head title="Staff without Gender" />
		<main class="max-w-7xl mx-auto sm:px-6 lg:px-8">
			<BreadCrumpVue :links="breadcrumbLinks" />
			<div class="overflow-hidden shadow-sm sm:rounded-lg px-6">
				<div class="py-6">
					<!-- Header -->
					<div class="mb-6">
						<h1
							class="text-3xl font-bold text-gray-900 dark:text-gray-100"
						>
							Staff without Gender
						</h1>
						<p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
							{{ staff.length }} active staff
							{{
								staff.length === 1 ? "member has" : "members have"
							}}
							missing gender information
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
							All active staff members have gender information recorded.
						</p>
					</div>

					<!-- Staff List -->
					<div v-else class="space-y-3">
						<Link
							v-for="member in staff"
							:key="member.id"
							:href="route('staff.show', member.id)"
							class="block rounded-lg border border-yellow-200 dark:border-yellow-800 bg-white dark:bg-gray-800 p-4 hover:bg-yellow-50 dark:hover:bg-yellow-900/10 transition-colors cursor-pointer"
						>
							<div class="flex items-center gap-4">
								<div
									class="flex-shrink-0 h-10 w-10 rounded-full bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center"
								>
									<UserIcon
										class="h-6 w-6 text-yellow-600 dark:text-yellow-400"
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
										<span v-if="member.rank">
											{{ member.rank }}
										</span>
										<span v-if="member.unit">
											{{ member.unit }}
										</span>
										<span v-if="member.hire_date_formatted">
											Hired: {{ member.hire_date_formatted }}
										</span>
									</div>
								</div>
							</div>
						</Link>
					</div>

					<div
						v-if="staff.length > 0"
						class="mt-6 rounded-md bg-blue-50 dark:bg-blue-900/20 p-4"
					>
						<p class="text-sm text-blue-700 dark:text-blue-300">
							<strong>Note:</strong> Gender information needs to be
							updated manually through the staff management
							interface. This cannot be automatically fixed.
						</p>
					</div>
				</div>
			</div>
		</main>
	</MainLayout>
</template>
