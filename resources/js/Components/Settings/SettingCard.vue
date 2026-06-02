<script setup>
import { Link } from "@inertiajs/vue3";
import { ChevronRightIcon } from "@heroicons/vue/16/solid";

defineProps({
	title: { type: String, required: true },
	count: { type: [Number, String], default: null },
	secondary: { type: String, default: null },
	href: { type: String, required: true },
	linkLabel: { type: String, default: "Manage" },
	icon: { type: [Object, Function], default: null },
});
</script>

<template>
	<Link
		:href="href"
		class="group flex flex-col rounded-2xl border border-green-200/60 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm p-5 transition hover:border-green-400 dark:hover:border-gray-500 hover:shadow"
	>
		<div class="flex items-center justify-between">
			<span
				class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-green-50 dark:bg-gray-700 text-green-600 dark:text-green-300"
			>
				<component :is="icon" v-if="icon" class="h-5 w-5" />
			</span>
			<ChevronRightIcon
				class="h-5 w-5 text-gray-300 group-hover:text-green-500 dark:text-gray-500"
			/>
		</div>
		<div class="mt-4">
			<p
				v-if="count !== null"
				class="text-2xl font-bold text-gray-900 dark:text-gray-50"
			>
				{{ count }}
			</p>
			<p class="text-sm font-semibold text-gray-700 dark:text-gray-200">
				{{ title }}
			</p>
			<p
				v-if="secondary"
				class="mt-0.5 text-xs text-gray-500 dark:text-gray-400"
			>
				{{ secondary }}
			</p>
		</div>
		<span
			class="mt-4 text-sm font-medium text-green-600 dark:text-green-300 group-hover:underline"
		>
			{{ linkLabel }} →
		</span>
	</Link>
</template>
