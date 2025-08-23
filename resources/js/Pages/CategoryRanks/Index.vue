<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import RankList from "./patials/RankList.vue";
import { Head } from "@inertiajs/vue3";
import TableHeader from "@/Components/TableHeader.vue";
import { useSearch } from "@/Composables/search";
import Pagination from "../../Components/Pagination.vue";
import { useNavigation } from "@/Composables/navigation";
import { computed } from "vue";
import { number } from "@formkit/icons";

const props = defineProps({
	ranks: {
		type: Object,
		default: () => {},
	},
	category: {
		type: String,
		default: () => {},
	},
	filters: { type: Object, default: () => {} },
});

const searchRanks = (value) => {
	useSearch(value, route("category-ranks.show", { category: props.category }));
};
const navigation = computed(() => useNavigation(props.ranks));
</script>

<template>
	<MainLayout>
		<Head title="Category Ranks" />
		<main class="max-w-7xl mx-auto sm:px-6 lg:px-8">
			<div class="overflow-hidden shadow-sm sm:rounded-lg px-6">
				<TableHeader
					title="Harmonized Grade"
					:total="ranks.total"
					:search="filters.search"
					action-text="Add Ranks"
					@action-clicked="toggle()"
					@search-entered="(value) => searchRanks(value)"
				/>

				<RankList :ranks="ranks.data" />
				<Pagination :navigation="navigation" />
			</div>
		</main>
	</MainLayout>
</template>
