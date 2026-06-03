<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, router } from "@inertiajs/vue3";
import BreadCrumpVue from "@/Components/BreadCrump.vue";

defineProps({
	appraisals: { type: Array, default: () => [] },
});

const open = (id) => router.visit(route("appraisal.show", { appraisal: id }));
const links = [{ name: "My Appraisals", url: "" }];
</script>

<template>
	<MainLayout>
		<Head title="My Appraisals" />
		<main class="max-w-5xl mx-auto sm:px-6 lg:px-8">
			<BreadCrumpVue :links="links" />
			<h1 class="text-2xl font-semibold my-4 dark:text-gray-100">My Appraisals</h1>

			<div v-if="appraisals.length" class="space-y-3">
				<button
					v-for="appraisal in appraisals"
					:key="appraisal.id"
					class="w-full text-left bg-white dark:bg-gray-800 rounded-lg shadow-sm p-5 hover:ring-2 hover:ring-green-500"
					@click="open(appraisal.id)"
				>
					<div class="flex items-center justify-between">
						<div>
							<p class="font-semibold dark:text-gray-100">{{ appraisal.cycle }}</p>
							<p class="text-sm text-gray-500 dark:text-gray-400">Appraiser: {{ appraisal.appraiser_name }} · Reviewer: {{ appraisal.reviewer_name }}</p>
						</div>
						<div class="text-right">
							<span :class="appraisal.status_color" class="font-semibold">{{ appraisal.status_label }}</span>
							<p v-if="appraisal.overall_score" class="text-sm text-gray-500 dark:text-gray-400">{{ appraisal.overall_score }} ({{ appraisal.overall_band }})</p>
						</div>
					</div>
				</button>
			</div>
			<p v-else class="text-gray-500 dark:text-gray-400">You have no appraisals yet.</p>
		</main>
	</MainLayout>
</template>
