<script setup>
/**
 * Retained as a thin wrapper so its existing consumers keep working.
 * New pages should use Components/UI/ListToolbar.vue directly.
 */
import ListToolbar from "@/Components/UI/ListToolbar.vue";
import InfoCard from "@/Components/InfoCard.vue";

const emit = defineEmits(["actionClicked", "searchEntered"]);

defineProps({
	title: { type: String, required: true },
	total: { type: Number, default: 0 },
	actionText: { type: String, default: "Add" },
	search: { type: String, default: "" },
	showAction: { type: Boolean, default: true },
	showSearch: { type: Boolean, default: true },
});
</script>
<template>
	<section class="my-2 flex flex-col gap-4">
		<InfoCard :title="title" :value="total" link="#" />
		<ListToolbar
			:title="title"
			:action-text="actionText"
			:search="search"
			:show-action="showAction"
			:show-search="showSearch"
			@action-clicked="emit('actionClicked')"
			@search-entered="(value) => emit('searchEntered', value)"
		/>
	</section>
</template>
