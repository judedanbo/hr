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

const submit = () => {
	form.put(route("app-settings.update"), { preserveScroll: true });
};

const fieldClass =
	"mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm";
const labelClass = "block text-sm font-medium text-gray-700 dark:text-gray-200";
const errorClass = "mt-1 text-xs text-red-600 dark:text-red-400";
</script>

<template>
	<Head title="Application settings" />
	<NewAuthenticated>
		<main class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 py-6">
			<BreadCrump :links="breadcrumbLinks" />

			<div class="mt-4 mb-6">
				<h1
					class="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-50"
				>
					Application settings
				</h1>
				<p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
					Organisation-wide configuration.
				</p>
			</div>

			<form class="space-y-6" @submit.prevent="submit">
				<section
					class="rounded-2xl border border-green-200/60 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm p-5"
				>
					<h2 class="text-sm font-semibold text-green-900 dark:text-gray-100">
						Branding
					</h2>
					<div class="mt-4 space-y-4">
						<div>
							<label :class="labelClass">Organization name</label>
							<input v-model="form.org_name" type="text" :class="fieldClass" />
							<p v-if="form.errors.org_name" :class="errorClass">
								{{ form.errors.org_name }}
							</p>
						</div>
						<div>
							<label :class="labelClass">Support email</label>
							<input
								v-model="form.support_email"
								type="email"
								:class="fieldClass"
							/>
							<p v-if="form.errors.support_email" :class="errorClass">
								{{ form.errors.support_email }}
							</p>
						</div>
					</div>
				</section>

				<section
					class="rounded-2xl border border-green-200/60 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm p-5"
				>
					<h2 class="text-sm font-semibold text-green-900 dark:text-gray-100">
						Display
					</h2>
					<div class="mt-4 space-y-4">
						<div>
							<label :class="labelClass">Date format</label>
							<input v-model="form.date_format" type="text" :class="fieldClass" />
							<p class="mt-1 text-xs text-gray-400">PHP date format, e.g. d M Y</p>
							<p v-if="form.errors.date_format" :class="errorClass">
								{{ form.errors.date_format }}
							</p>
						</div>
						<div>
							<label :class="labelClass">Records per page</label>
							<input
								v-model.number="form.pagination_size"
								type="number"
								min="5"
								max="100"
								:class="fieldClass"
							/>
							<p v-if="form.errors.pagination_size" :class="errorClass">
								{{ form.errors.pagination_size }}
							</p>
						</div>
					</div>
				</section>

				<section
					class="rounded-2xl border border-green-200/60 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm p-5"
				>
					<h2 class="text-sm font-semibold text-green-900 dark:text-gray-100">
						Security
					</h2>
					<div class="mt-4 space-y-4">
						<div>
							<label :class="labelClass">Password change interval (days)</label>
							<input
								v-model.number="form.password_change_interval_days"
								type="number"
								min="0"
								max="3650"
								:class="fieldClass"
							/>
							<p class="mt-1 text-xs text-gray-400">0 disables forced rotation.</p>
							<p
								v-if="form.errors.password_change_interval_days"
								:class="errorClass"
							>
								{{ form.errors.password_change_interval_days }}
							</p>
						</div>
					</div>
				</section>

				<div class="flex justify-end">
					<button
						type="submit"
						:disabled="form.processing"
						class="rounded-md bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 disabled:opacity-50 dark:bg-gray-600 dark:hover:bg-gray-500"
					>
						Save
					</button>
				</div>
			</form>
		</main>
	</NewAuthenticated>
</template>
