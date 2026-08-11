<script setup>
import NewAuthenticated from "@/Layouts/NewAuthenticated.vue";
import { Head, useForm } from "@inertiajs/vue3";
import PageShell from "@/Components/UI/PageShell.vue";
import PageHeader from "@/Components/UI/PageHeader.vue";

const props = defineProps({
	general: { type: Object, required: true },
	security: { type: Object, required: true },
});

const breadcrumbLinks = [
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
	"mt-1 block w-full rounded-lg border-gray-300 bg-white text-gray-900 shadow-sm focus:border-green-500 focus:ring-1 focus:ring-green-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:focus:border-green-400 dark:focus:ring-green-400 sm:text-sm";
const labelClass = "block text-sm font-medium text-gray-700 dark:text-gray-200";
const errorClass = "mt-1 text-xs text-red-600 dark:text-red-400";
</script>

<template>
	<Head title="Application settings" />
	<NewAuthenticated>
		<PageShell>
			<PageHeader
				title="Application settings"
				description="Organisation-wide configuration."
				:breadcrumbs="breadcrumbLinks"
			/>

			<form class="space-y-6" @submit.prevent="submit">
				<section
					class="rounded-2xl border border-green-200/60 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800"
				>
					<h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
						Branding
					</h2>
					<div class="mt-4 space-y-4">
						<div>
							<label :class="labelClass">Organization name</label>
							<input
								v-model="form.org_name"
								type="text"
								dusk="org_name"
								:class="fieldClass"
							/>
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
					class="rounded-2xl border border-green-200/60 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800"
				>
					<h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
						Display
					</h2>
					<div class="mt-4 space-y-4">
						<div>
							<label :class="labelClass">Date format</label>
							<input
								v-model="form.date_format"
								type="text"
								:class="fieldClass"
							/>
							<p class="mt-1 text-xs text-gray-400">
								PHP date format, e.g. d M Y
							</p>
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
								dusk="pagination_size"
								:class="fieldClass"
							/>
							<p v-if="form.errors.pagination_size" :class="errorClass">
								{{ form.errors.pagination_size }}
							</p>
						</div>
					</div>
				</section>

				<section
					class="rounded-2xl border border-green-200/60 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800"
				>
					<h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
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
							<p class="mt-1 text-xs text-gray-400">
								0 disables forced rotation.
							</p>
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
						dusk="save"
						:disabled="form.processing"
						class="btn btn-primary"
					>
						Save
					</button>
				</div>
			</form>
		</PageShell>
	</NewAuthenticated>
</template>
