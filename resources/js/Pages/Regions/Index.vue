<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, usePage } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import { useToggle } from "@vueuse/core";
import RegionTable from "./partials/RegionTable.vue";
import PageHeader from "@/Components/PageHeader.vue";
import { useNavigation } from "@/Composables/navigation";
import { useSearch } from "@/Composables/search";
import Pagination from "@/Components/Pagination.vue";
import { ArrowDownTrayIcon } from "@heroicons/vue/24/outline";
import NoPermission from "@/Components/NoPermission.vue";

const navigation = computed(() => useNavigation(props.regions));

let openAddDialog = ref(false);

const page = usePage();
const permissions = computed(() => page.props?.auth.permissions);

let toggle = useToggle(openAddDialog);

let props = defineProps({
	regions: { type: Object, required: true },
	filters: { type: Object, default: () => {} },
});

let BreadCrumpLinks = [
	{
		name: "Regions",
	},
];

let search = ref(props.filters.search);
const searchRegions = (value) => {
	useSearch(value, route("region.index"));
};

let openRegion = (region) => {
	router.visit(route("region.show", { region: region }));
};
</script>

<template>
	<MainLayout>
		<!-- {{ regions.data[0].institution_id }} -->
		<Head title="Regions" />
		<main class="max-w-7xl mx-auto sm:px-6 lg:px-8">
			<BreadCrumpVue :links="BreadCrumpLinks" />
			<div
				class="overflow-hidden shadow-sm sm:rounded-lg px-6 border-b border-gray-200"
			>
				<PageHeader
					title="Regions"
					:subtitle="`Total Regions: ${regions.total}`"
					:total="regions.total"
					:search="search"
					@action-clicked="toggle()"
					@search-entered="(value) => searchRegions(value)"
				/>
				<div
					v-if="permissions?.includes('download regions')"
					class="flex gap-x-5"
				>
					<a
						class="rounded-md flex gap-x-3 bg-green-600 dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600"
						:href="route('region.summary')"
					>
						<arrow-down-tray-icon class="-ml-1.5 h-5 w-5" aria-hidden="true" />
						Download Regional Summary
					</a>
				</div>

				<RegionTable
					:regions="regions.data"
					@open-region="(region) => openRegion(region)"
				>
					<template #pagination>
						<Pagination :navigation="navigation" />
					</template>
				</RegionTable>
			</div>
		</main>
		<!-- <NoPermission v-else /> -->
	</MainLayout>
</template>
