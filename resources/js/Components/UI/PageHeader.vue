<script setup>
import BreadCrump from "@/Components/BreadCrump.vue";

defineProps({
	title: { type: String, required: true },
	description: { type: String, default: null },
	breadcrumbs: { type: Array, default: () => [] },
	count: { type: [Number, String], default: null },
});
</script>
<template>
	<div>
		<BreadCrump :links="breadcrumbs" />
		<div class="mt-4 sm:flex sm:items-start sm:justify-between sm:gap-4">
			<div class="min-w-0">
				<div class="flex items-center gap-2">
					<h1
						class="text-xl sm:text-2xl font-semibold tracking-tight text-gray-900 dark:text-gray-50"
					>
						{{ title }}
					</h1>
					<span
						v-if="count !== null"
						class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium tabular-nums text-gray-600 ring-1 ring-inset ring-gray-500/10 dark:bg-gray-700/50 dark:text-gray-300 dark:ring-gray-400/20"
					>
						{{ typeof count === "number" ? count.toLocaleString() : count }}
					</span>
				</div>
				<p
					v-if="description"
					class="mt-1 text-sm text-gray-500 dark:text-gray-400"
				>
					{{ description }}
				</p>
				<slot name="meta" />
			</div>
			<div class="mt-4 flex shrink-0 items-center gap-2 sm:mt-0">
				<slot name="actions" />
			</div>
		</div>
	</div>
</template>
