<script setup>
/**
 * Retained as a thin wrapper so its existing consumers keep working.
 * New pages should use Components/UI/ListToolbar.vue directly, alongside
 * Components/UI/PageHeader.vue for the title block.
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
	<section class="my-2 flex flex-col gap-4">
		<div
			v-if="hasStats"
			class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4"
		>
			<InfoCard
				v-for="(stat, index) in stats"
				:key="index"
				:title="stat.title"
				:value="stat.value"
				link="#"
			/>
		</div>
		<InfoCard v-else :title="title" :value="total" link="#" />

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
