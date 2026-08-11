<script setup>
/**
 * Retained as a thin wrapper so its existing consumers keep working.
 * New pages should use Components/UI/PageHeader.vue + Components/UI/ListToolbar.vue.
 */
import { computed } from "vue";
import ListToolbar from "@/Components/UI/ListToolbar.vue";
import InfoCard from "@/Components/InfoCard.vue";

const emit = defineEmits(["actionClicked", "searchEntered"]);

const props = defineProps({
	title: { type: String, required: true },
	total: { type: Number, default: 0 },
	stats: { type: Array, default: null },
	actionText: { type: String, default: "Add" },
	search: { type: String, default: "" },
	addPermission: { type: Boolean, default: true },
});

const hasStats = computed(() => props.stats && props.stats.length > 0);
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

		<div
			v-if="hasStats"
			class="grid grid-cols-1 gap-4 rounded-2xl border border-green-200/60 bg-white p-5 shadow-sm sm:grid-cols-2 lg:grid-cols-4 dark:border-gray-700 dark:bg-gray-800"
		>
			<InfoCard
				v-for="(stat, index) in stats"
				:key="index"
				:title="stat.title"
				:value="stat.value"
			/>
		</div>

		<ListToolbar
			:title="title"
			:action-text="actionText"
			:search="search"
			:show-action="addPermission"
			@action-clicked="emit('actionClicked')"
			@search-entered="(value) => emit('searchEntered', value)"
		/>
	</section>
</template>
