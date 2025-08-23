<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import Pagination from "@/Components/Pagination.vue";
import CurrentPromotions from "./partials/CurrentPromotions.vue";
import { ref, computed } from "vue";
import { Head } from "@inertiajs/vue3";
import { router } from "@inertiajs/vue3";
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

const exportToExcel = () => {
	window.location = route("export.promotion-list");
};

const openPromotion = (
	jobId,
	batch = null,
	year = new Date().getFullYear(),
) => {
	router.get(route("promotion.batch.show", { rank: jobId, year: year }), {
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
			<CurrentPromotions
				:promotions="promotions"
				@update:model-value="searchStaff"
				@openPromotion="
					(jobId, batch, year) => openPromotion(jobId, batch, year)
				"
			>
				<template #pagination>
					<Pagination :navigation="navigation" />
				</template>
			</CurrentPromotions>
		</main>
	</MainLayout>
</template>
