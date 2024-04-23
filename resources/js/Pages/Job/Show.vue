<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, Link } from "@inertiajs/inertia-vue3";
import { Inertia } from "@inertiajs/inertia";
import RankOverview from "./partials/RankOverview.vue";
import RankStaff from "./partials/RankStaff.vue";
import RankPromote from "./partials/RankPromote.vue";
import AllStaff from "./partials/AllStaff.vue";
import { ref, watch } from "vue";
import { debouncedWatch } from "@vueuse/core";
import PageTitle from "@/Components/PageTitle.vue";
import PageHeading from "@/Components/PageHeading.vue";
let props = defineProps({
	job: Object,
	filters: Object,
});
let search = ref(props.filters.search);
debouncedWatch(
	search,
	() => {
		Inertia.get(
			route("job.show", {
				job: props.job.id,
			}),
			{ search: search.value },
			{ preserveState: true, replace: true, preserveScroll: true },
		);
	},
	{ debounce: 300 },
);
const changeTab = (tab) => {
	currentTab.value = tab;
	tabs.map((t) => {
		t.current = t.name === tab.name;
	});
};
const components = {
	RankOverview,
	AllStaff,
	RankStaff,
	RankPromote,
};

const tabs = [
	{ name: "Overview", component: "RankOverview", href: "#", current: true },
	// { name: "Active", component: "RankActive", href: "#", current: false },
	{ name: "Current Staff", component: "RankStaff", href: "#", current: false },
	{
		name: "Due for Promotion",
		component: "RankPromote",
		href: "#",
		current: false,
	},
	{ name: "All Time", component: "AllStaff", href: "#", current: false },
];
const currentTab = ref(tabs[0]);

const startSearch = (value) => {
	search.value = value;
};

const reload = () => {
	this.$forceUpdate();
};
const selectedStaff = ref([]);
const updateStaffList = (staffList) => {
	selectedStaff.value = staffList;
};
</script>
<template>
	<Head :title="job.name" />
	<MainLayout>
		<main class="max-w-7xl mx-auto sm:px-6 lg:px-8 pt-8">
			<PageHeading
				:name="job.name"
				@searchStaff="(searchValue) => startSearch(searchValue)"
				:search="search"
			>
			</PageHeading>
			<PageTitle
				:tabs="tabs"
				@tab-clicked="(tab) => changeTab(tab)"
				:current="components[currentTab.component]"
			/>
			<component
				:is="components[currentTab.component]"
				v-bind="{ rank: job.id, search, staffList: selectedStaff }"
				@updateStaffList="(staffList) => updateStaffList(staffList)"
			/>
		</main>
		<!-- {{ selectedStaff }} -->
	</MainLayout>
</template>
