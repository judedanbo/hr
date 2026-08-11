<script setup>
/**
 * Retained as a thin wrapper so Staff/Index keeps working.
 * New pages should use Components/UI/ListToolbar.vue directly.
 */
import ListToolbar from "@/Components/UI/ListToolbar.vue";

const emit = defineEmits(["actionClicked", "searchEntered"]);

defineProps({
	title: { type: String, required: true },
	total: { type: Number, default: 0 },
	actionText: { type: String, default: "Add" },
	actionPermission: { type: Boolean, default: false },
	search: { type: String, default: "" },
});
</script>
<template>
	<section class="flex flex-col gap-4">
		<div class="flex items-center gap-2">
			<h1
				class="text-xl sm:text-2xl font-semibold tracking-tight text-gray-900 dark:text-gray-50"
			>
				{{ title }}
			</h1>
			<span
				class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium tabular-nums text-gray-600 ring-1 ring-inset ring-gray-500/10 dark:bg-gray-700/50 dark:text-gray-300 dark:ring-gray-400/20"
			>
				{{ total.toLocaleString() }}
			</span>
		</div>
		<ListToolbar
			:title="title"
			:action-text="actionText"
			:search="search"
			:show-action="actionPermission"
			@action-clicked="emit('actionClicked')"
			@search-entered="(value) => emit('searchEntered', value)"
		/>
	</section>
</template>
