<script setup>
import { Head } from "@inertiajs/vue3";
import NewAuthenticated from "@/Layouts/NewAuthenticated.vue";
import BreadCrump from "@/Components/BreadCrump.vue";

defineProps({
	content: {
		type: String,
		required: true,
	},
});

const breadcrumbs = [{ name: "Help", url: route("help.index") }];
</script>

<template>
	<Head title="Help & Documentation" />

	<NewAuthenticated>
		<div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
			<div class="overflow-hidden shadow-sm sm:rounded-lg">
				<div class="p-6 border-b border-gray-200 dark:border-gray-700">
					<BreadCrump :links="breadcrumbs" />
					<h1
						class="text-3xl font-bold text-gray-900 dark:text-gray-50 my-4"
					>
						Help & Documentation
					</h1>
				</div>
			</div>

			<div
				class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mt-6"
			>
				<div class="p-6">
					<article
						class="prose prose-lg dark:prose-invert max-w-none prose-headings:text-gray-900 dark:prose-headings:text-gray-100 prose-a:text-green-600 dark:prose-a:text-green-400 prose-code:text-green-600 dark:prose-code:text-green-400"
						v-html="content"
					></article>
				</div>
			</div>
		</div>
	</NewAuthenticated>
</template>
