<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import Pagination from "@/Components/Pagination.vue";
import CurrentPromotions from "./partials/CurrentPromotions.vue";
import { ref, computed } from "vue";
import { Head } from "@inertiajs/inertia-vue3";
import { Inertia } from "@inertiajs/inertia";
import PageHeader from "@/Components/PageHeader.vue";
import { useNavigation } from "@/Composables/navigation";
import { useSearch } from "@/Composables/search";
let props = defineProps({
	promotions: Object,
	filters: Object,
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
	window.location = route("export.promotion-list");
};

const openPromotion = (
	jobId,
	batch = null,
	year = new Date().getFullYear(),
) => {
	// console.log(batch);
	Inertia.get(route("promotion.batch.show", { year: year }), {
		rank: jobId,
		batch: batch,
	});
};
</script>

<template>
	<MainLayout>
		<Head title="Next Promotion list" />
		<main class="max-w-7xl mx-auto sm:px-6 lg:px-8">
			<div
				class="overflow-hidden shadow-sm sm:rounded-lg px-6 border-b border-gray-200"
			>
				<PageHeader
					title="Next Promotion list"
					:total="promotions.total"
					:search="search"
					action-text="Export Summary"
					@action-clicked="exportToExcel()"
					@search-entered="(value) => searchStaff(value)"
				/>
			</div>
			<div class="flex justify-end mt-4">
				<a
					class="rounded-md flex gap-x-3 bg-green-600 dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600"
					:href="route('export.promotion')"
				>
					Export Data to excel
				</a>
			</div>
			<CurrentPromotions
				@update:model-value="searchStaff"
				@openPromotion="
					(jobId, batch, year) => openPromotion(jobId, batch, year)
				"
				:promotions="promotions"
			>
				<template #pagination>
					<Pagination :navigation="navigation" />
				</template>
			</CurrentPromotions>
		</main>
	</MainLayout>
</template>
