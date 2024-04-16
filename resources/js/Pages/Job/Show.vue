<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, Link } from "@inertiajs/inertia-vue3";
import Tab from "@/Components/Tab.vue";
import { Inertia } from "@inertiajs/inertia";
import { MagnifyingGlassIcon, PlusIcon } from "@heroicons/vue/24/outline";
import PageHeader from "@/Components/PageHeader.vue";
import { format, differenceInYears } from "date-fns";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import BreezeInput from "@/Components/Input.vue";
import RankOverview from "./partials/RankOverview.vue";
import RankStaff from "./partials/RankStaff.vue";
// import RankActive from "./partials/RankActive.vue";
import RankPromote from "./partials/RankPromote.vue";
import AllStaff from "./partials/AllStaff.vue";
import { ref, watch } from "vue";
import debounce from "lodash/debounce";
import InfoCard from "@/Components/InfoCard.vue";
import Avatar from "../Person/partials/Avatar.vue";
import { Menu, MenuButton, MenuItem, MenuItems } from "@headlessui/vue";
import { EllipsisVerticalIcon } from "@heroicons/vue/20/solid";
import PageTitle from "@/Components/PageTitle.vue";
import PageHeading from "@/Components/PageHeading.vue";
import PageActions from "@/Components/PageActions.vue";
import PageStats from "@/Components/PageStats.vue";
let props = defineProps({
	job: Object,
	filters: Object,
});
let search = ref(props.filters.search);
watch(
	search,
	debounce(function (value) {
		Inertia.get(
			route("job.show", {
				job: props.job.id,
			}),
			{ search: value },
			{ preserveState: true, replace: true, preserveScroll: true },
		);
	}, 300),
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
</script>
<template>
	<Head :title="job.name" />
	<MainLayout>
		<main class="max-w-7xl mx-auto sm:px-6 lg:px-8 pt-8">
			<PageHeading
				:name="job.name"
				@search="(search) => startSearch(search)"
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
				v-bind="{ rank: job.id, search }"
			/>
		</main>
	</MainLayout>
</template>
