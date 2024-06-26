<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, Link } from "@inertiajs/inertia-vue3";
import { Inertia } from "@inertiajs/inertia";
import PositionOverview from "./partials/PositionOverview.vue";
// import RankStaff from "./partials/RankStaff.vue";
// import RankPromote from "./partials/RankPromote.vue";
// import AllStaff from "./partials/AllStaff.vue";
import { ref, watch } from "vue";
import { debouncedWatch } from "@vueuse/core";
import PageTitle from "@/Components/PageTitle.vue";
import PageHeading from "@/Components/PageHeading.vue";
import Modal from "@/Components/NewModal.vue";
// import EditRank from "./partials/EditRank.vue";
import { useToggle } from "@vueuse/core";
// import DeletePosition from "./partials/DeletePosition.vue";
let props = defineProps({
	position: Object,
	filters: Object,
});
let search = ref(props.filters.search);
debouncedWatch(
	search,
	() => {
		Inertia.get(
			route("position.show", {
				position: props.position.id,
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
	PositionOverview,
	// AllStaff,
	// RankStaff,
	// RankPromote,
};

const tabs = [
	{
		name: "Occupants",
		component: "PositionOverview",
		href: "#",
		current: true,
	},
	// { name: "Active", component: "RankActive", href: "#", current: false },
	// { name: "Current Staff", component: "RankStaff", href: "#", current: false },
	// {
	// 	name: "Due for Promotion",
	// 	component: "RankPromote",
	// 	href: "#",
	// 	current: false,
	// },
	// { name: "All Time", component: "AllStaff", href: "#", current: false },
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

const emit = defineEmits(["addRank", "editRank", "deleteRank"]);
const openEditDialog = ref(false);
const toggleEditModal = useToggle(openEditDialog);

const openConfirmDeleteDialog = ref(false);
const toggleDeleteModal = useToggle(openConfirmDeleteDialog);

const deletePosition = () => {
	Inertia.delete(route("position.delete", { position: props.position.id }));
};
</script>
<template>
	<Head :title="position.name" />
	<MainLayout>
		<main class="max-w-7xl mx-auto sm:px-6 lg:px-8 pt-8">
			<PageHeading
				:name="position.name"
				@searchStaff="(searchValue) => startSearch(searchValue)"
				:search="search"
			/>
			<div class="flex gap-4 justify-end pt-4 sm:ml-16 sm:mt-0 sm:flex-none">
				<button
					type="button"
					class="block rounded-md bg-green-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600"
					@click="toggleEditModal()"
				>
					Edit rank
				</button>

				<button
					type="button"
					class="block rounded-md bg-rose-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-rose-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-rose-900"
					@click="toggleDeleteModal()"
				>
					Delete rank
				</button>
			</div>
			<PageTitle
				:tabs="tabs"
				@tab-clicked="(tab) => changeTab(tab)"
				:current="components[currentTab.component]"
			/>
			<component
				:is="components[currentTab.component]"
				v-bind="{ staff: position.staff, search, staffList: selectedStaff }"
				@updateStaffList="(staffList) => updateStaffList(staffList)"
				class="mt-4"
			/>
		</main>
		<Modal @close="toggleEditModal()" :show="openEditDialog">
			<EditRank :position="position" @formSubmitted="toggleEditModal()" />
		</Modal>
		<Modal :show="openConfirmDeleteDialog" @close="toggleDeleteModal()">
			<DeletePosition
				:position="position"
				@close="toggleDeleteModal()"
				@delete-confirmed="deletePosition()"
			/>
		</Modal>
	</MainLayout>
</template>
