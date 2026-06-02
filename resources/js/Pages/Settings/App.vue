<script setup>
import NewAuthenticated from "@/Layouts/NewAuthenticated.vue";
import { Head, useForm } from "@inertiajs/vue3";
import BreadCrump from "@/Components/BreadCrump.vue";

const props = defineProps({
	general: { type: Object, required: true },
	security: { type: Object, required: true },
});

const breadcrumbLinks = [
	{ name: "Home", url: "/dashboard" },
	{ name: "Settings", url: "/settings" },
	{ name: "Application", url: null },
];

const form = useForm({
	org_name: props.general.org_name,
	support_email: props.general.support_email,
	date_format: props.general.date_format,
	pagination_size: props.general.pagination_size,
	password_change_interval_days: props.security.password_change_interval_days,
});

function submit() {
	form.put(route("app-settings.update"), { preserveScroll: true });
}
</script>

<template>
	<Head title="Application Settings" />
	<NewAuthenticated>
		<main class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 py-6">
			<BreadCrump :links="breadcrumbLinks" />

			<div class="mt-4">
				<h1
					class="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-50"
				>
					Application Settings
				</h1>
				<p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
					Manage general and security configuration for the application.
				</p>
			</div>

			<form @submit.prevent="submit" class="mt-6 space-y-6">
				<div class="space-y-4">
					<div>
						<label
							for="org_name"
							class="block text-sm font-medium text-gray-700 dark:text-gray-300"
						>
							Organisation name
						</label>
						<input
							id="org_name"
							v-model="form.org_name"
							type="text"
							class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100"
						/>
						<p v-if="form.errors.org_name" class="mt-1 text-sm text-red-600">
							{{ form.errors.org_name }}
						</p>
					</div>

					<div>
						<label
							for="support_email"
							class="block text-sm font-medium text-gray-700 dark:text-gray-300"
						>
							Support email
						</label>
						<input
							id="support_email"
							v-model="form.support_email"
							type="email"
							class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100"
						/>
						<p
							v-if="form.errors.support_email"
							class="mt-1 text-sm text-red-600"
						>
							{{ form.errors.support_email }}
						</p>
					</div>

					<div>
						<label
							for="date_format"
							class="block text-sm font-medium text-gray-700 dark:text-gray-300"
						>
							Date format
						</label>
						<input
							id="date_format"
							v-model="form.date_format"
							type="text"
							class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100"
						/>
						<p v-if="form.errors.date_format" class="mt-1 text-sm text-red-600">
							{{ form.errors.date_format }}
						</p>
					</div>

					<div>
						<label
							for="pagination_size"
							class="block text-sm font-medium text-gray-700 dark:text-gray-300"
						>
							Pagination size
						</label>
						<input
							id="pagination_size"
							v-model.number="form.pagination_size"
							type="number"
							class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100"
						/>
						<p
							v-if="form.errors.pagination_size"
							class="mt-1 text-sm text-red-600"
						>
							{{ form.errors.pagination_size }}
						</p>
					</div>

					<div>
						<label
							for="password_change_interval_days"
							class="block text-sm font-medium text-gray-700 dark:text-gray-300"
						>
							Password change interval (days)
						</label>
						<input
							id="password_change_interval_days"
							v-model.number="form.password_change_interval_days"
							type="number"
							class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100"
						/>
						<p
							v-if="form.errors.password_change_interval_days"
							class="mt-1 text-sm text-red-600"
						>
							{{ form.errors.password_change_interval_days }}
						</p>
					</div>
				</div>

				<div class="flex justify-end">
					<button
						type="submit"
						:disabled="form.processing"
						class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50"
					>
						Save changes
					</button>
				</div>
			</form>
		</main>
	</NewAuthenticated>
</template>
