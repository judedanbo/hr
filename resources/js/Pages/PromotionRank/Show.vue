<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import Pagination from "@/Components/Pagination.vue";
import PromotionList from "./PromotionList.vue";
import { ref, computed } from "vue";
import { Head } from "@inertiajs/inertia-vue3";
import { Inertia } from "@inertiajs/inertia";
import PageHeader from "@/Components/PageHeader.vue";
import { useNavigation } from "@/Composables/navigation";
import { useSearch } from "@/Composables/search";
let props = defineProps({
	promotions: Object,
	rank: {
		type: String,
		required: true,
	},
	filters: Object,
	rank: {
		type: String,
		required: true,
	},
});

let search = ref(props.filters.search);

const searchStaff = (value) => {
	const year = ref(props.filters.year);
	const month = ref(props.filters.month);
	useSearch(
		value,
		route("promotion.batch.show", { year: year.value, month: month.value }),
	);
};
const navigation = computed(() => useNavigation(props.promotions));

// const searchStaff = (value) => {
// 	Inertia.get(
// 		route("promotion.batch.show", { year: year.value, month: month.value }),
// 		{ search: value },
// 		{ preserveState: true, replace: true, preserveScroll: true },
// 	);
// };

// watch(search, (value) => {
// 	Inertia.get(
// 		route("promotion.batch.show", { year: 2023 }),
// 		{ search: value },
// 		{ preserveState: true, replace: true, preserveScroll: true },
// 	);
// });
const exportToExcel = () => {
	window.location = route("export.promotion") + "?rank=" + props.rank;
};
</script>

<template>
	<MainLayout>
		<Head title="Next Promotion list" />
		<main class="max-w-7xl mx-auto sm:px-6 lg:px-8">
			<!-- <BreadCrumpVue :links="BreadCrumpLinks" /> -->
			<div
				class="overflow-hidden shadow-sm sm:rounded-lg px-6 border-b border-gray-200"
			>
				<PageHeader
					title="Next Promotion list"
					:total="promotions.total"
					:search="search"
					action-text="Export Data to excel"
					@action-clicked="exportToExcel()"
					@search-entered="(value) => searchStaff(value)"
				/>
			</div>
			<PromotionList @update:model-value="searchStaff" :promotions="promotions">
				<template #pagination>
					<Pagination :navigation="navigation" />
				</template>
			</PromotionList>
		</main>
	</MainLayout>
</template>
<style>
input::placeholder {
	@apply dark:text-gray-300;
}
</style>
