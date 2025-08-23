<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, Link } from "@inertiajs/vue3";
import Pagination from "@/Components/Pagination.vue";
import { watch, ref } from "vue";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import { router } from "@inertiajs/vue3";
import Promotion from "./Promotion.vue";

let props = defineProps({
	promotions: Object,
	filters: Object,
});

let search = ref(props.filters.search);

watch(search, (value) => {
	router.get(
		route("promotion.index"),
		{ search: value },
		{ preserveState: true, replace: true, preserveScroll: true },
	);
});
</script>

<template>
	<Head title="Promotions" />

	<MainLayout>
		<template #header>
			<h2
				class="font-semibold text-xl text-gray-800 leading-tight dark:text-gray-50"
			>
				Promotions
			</h2>
		</template>

		<div>
			<div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
				<h2
					class="mx-auto max-w-2xl text-base font-semibold leading-6 text-gray-900 dark:text-gray-50 lg:mx-0 lg:max-w-none"
				>
					Promotion History
				</h2>
				<input v-model="search" type="search" />
			</div>
			<div
				class="mt-6 overflow-hidden border-t border-gray-100 dark:border-gray-800"
			>
				<div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
					<div class="mx-auto max-w-2xl lg:mx-0 lg:max-w-none">
						<PromotionByRank :promotions="promotions" />
					</div>
				</div>
			</div>
		</div>
	</MainLayout>
</template>
