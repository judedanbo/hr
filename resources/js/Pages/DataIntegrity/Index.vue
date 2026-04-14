<script setup>
import { Head } from "@inertiajs/vue3";
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import IntegrityCheckCard from "@/Components/IntegrityCheckCard.vue";

const props = defineProps({
	checks: {
		type: Array,
		required: true,
	},
});

const breadcrumbLinks = [
	{
		name: "Data Integrity",
	},
];
</script>

<template>
	<MainLayout>
		<Head title="Data Integrity Checks" />
		<main class="max-w-7xl mx-auto sm:px-6 lg:px-8">
			<BreadCrumpVue :links="breadcrumbLinks" />
			<div class="overflow-hidden shadow-sm sm:rounded-lg px-6">
				<div class="py-6">
					<div class="mb-6">
						<h1
							class="text-3xl font-bold text-gray-900 dark:text-gray-100"
						>
							Data Integrity Checks
						</h1>
						<p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
							Monitor and fix data integrity issues across the system
						</p>
					</div>

					<div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
						<IntegrityCheckCard
							v-for="check in checks"
							:key="check.id"
							:title="check.title"
							:description="check.description"
							:count="check.count"
							:severity="check.severity"
							:href="check.route"
						/>
					</div>
				</div>
			</div>
		</main>
	</MainLayout>
</template>
