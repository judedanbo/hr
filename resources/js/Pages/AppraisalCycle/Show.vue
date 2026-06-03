<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head } from "@inertiajs/vue3";
import BreadCrumpVue from "@/Components/BreadCrump.vue";

const props = defineProps({
	cycle: { type: Object, required: true },
});

const links = [
	{ name: "Appraisal Cycles", url: route("appraisal-cycle.index") },
	{ name: props.cycle.name, url: "" },
];

const windows = [
	{ label: "Objective setting", start: props.cycle.objective_window_start, end: props.cycle.objective_window_end },
	{ label: "Mid-year review", start: props.cycle.midyear_window_start, end: props.cycle.midyear_window_end },
	{ label: "End-of-cycle", start: props.cycle.final_window_start, end: props.cycle.final_window_end },
];
</script>

<template>
	<MainLayout>
		<Head :title="cycle.name" />
		<main class="max-w-7xl mx-auto sm:px-6 lg:px-8">
			<BreadCrumpVue :links="links" />
			<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mt-4">
				<div class="flex items-center justify-between">
					<h1 class="text-2xl font-semibold dark:text-gray-100">{{ cycle.name }}</h1>
					<span :class="cycle.status_color" class="font-semibold">{{ cycle.status_label }}</span>
				</div>
				<p class="text-gray-500 dark:text-gray-300 mt-1">Year {{ cycle.year }}</p>

				<div class="grid grid-cols-2 gap-4 mt-6">
					<div class="rounded-md bg-gray-50 dark:bg-gray-700 p-4">
						<p class="text-sm text-gray-500 dark:text-gray-300">Objectives weight</p>
						<p class="text-xl font-semibold dark:text-gray-100">{{ cycle.objectives_weight }}%</p>
					</div>
					<div class="rounded-md bg-gray-50 dark:bg-gray-700 p-4">
						<p class="text-sm text-gray-500 dark:text-gray-300">Competencies weight</p>
						<p class="text-xl font-semibold dark:text-gray-100">{{ cycle.competencies_weight }}%</p>
					</div>
				</div>

				<h2 class="text-lg font-semibold mt-8 mb-2 dark:text-gray-100">Windows</h2>
				<ul class="divide-y divide-gray-200 dark:divide-gray-700">
					<li v-for="w in windows" :key="w.label" class="flex justify-between py-2 text-sm dark:text-gray-200">
						<span>{{ w.label }}</span>
						<span>{{ w.start ?? "—" }} → {{ w.end ?? "—" }}</span>
					</li>
				</ul>

				<p class="mt-8 text-sm text-gray-500 dark:text-gray-300">
					{{ cycle.appraisals_count }} appraisal(s) in this cycle.
				</p>
			</div>
		</main>
	</MainLayout>
</template>
