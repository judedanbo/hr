<script setup>
import { computed } from "vue";
import { Link } from "@inertiajs/vue3";

const props = defineProps({
	count: { type: Number, required: true },
	sparkline: { type: Array, required: true },
	linkTo: { type: String, default: "/data-integrity/pending-qualifications" },
	title: { type: String, default: "Pending Qualification Approvals" },
});

const max = computed(() => Math.max(1, ...props.sparkline));
const bars = computed(() => props.sparkline.map((v) => (v / max.value) * 100));
</script>

<template>
	<div class="h-full bg-white dark:bg-gray-800 rounded-lg shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700 p-4 flex flex-col">
		<div class="flex items-baseline justify-between">
			<h3 class="text-sm font-medium text-gray-600 dark:text-gray-300">{{ title }}</h3>
			<Link :href="linkTo" class="text-xs text-indigo-600 hover:underline">View &rarr;</Link>
		</div>
		<div class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
			{{ count.toLocaleString() }}
		</div>
		<div class="mt-auto flex items-end gap-0.5 h-12">
			<div
				v-for="(h, i) in bars"
				:key="i"
				class="flex-1 bg-indigo-400 dark:bg-indigo-500 rounded-sm"
				:style="{ height: h + '%', minHeight: '2px' }"
			></div>
		</div>
		<div class="text-[10px] text-gray-400 mt-1">Submissions, last 30 days</div>
	</div>
</template>
