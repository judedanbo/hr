<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
// import Pagination from '@/Components/Pagination.vue'
import Promotion from "./Promotions.vue";
import { ref, watch } from "vue";
import { Inertia } from "@inertiajs/inertia";
let props = defineProps({
	promotions: Object,
	filters: Object,
});

let search = ref(props.filters.search);
let year = ref(props.filters.year);
let month = ref(props.filters.month);

const searchStaff = (value) => {
	Inertia.get(
		route("promotion.batch.show", { year: year.value, month: month.value }),
		{ search: value },
		{ preserveState: true, replace: true, preserveScroll: true },
	);
};

watch(search, (value) => {
	Inertia.get(
		route("promotion.batch.show", { year: 2023 }),
		{ search: value },
		{ preserveState: true, replace: true, preserveScroll: true },
	);
});
</script>

<template>
	<MainLayout class="px-8 py-4">
		<h1 class="text-2xl px-4 py-4 dark:text-gray-100">Promotion List</h1>
		<div class="sm:flex sm:items-center justify-between px-8">
			<FormKit
				v-model="search"
				prefix-icon="search"
				type="search"
				placeholder="Search..."
				autofocus
			/>
			<a
				:href="route('export.promotion')"
				type="button"
				class="block rounded-md bg-green-600 dark:bg-gray-700 px-3 py-1.5 text-center text-sm font-semibold leading-6 text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600 w-48"
			>
				Export Data to excel
			</a>
		</div>
		<Promotion @update:model-value="searchStaff" :promotions="promotions" />
	</MainLayout>
</template>
<style>
input::placeholder {
	@apply dark:text-gray-300;
}
</style>
