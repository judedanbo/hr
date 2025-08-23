<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, Link, usePage } from "@inertiajs/vue3";
import { router } from "@inertiajs/vue3";
import RankOverview from "./partials/RankOverview.vue";
import RankStaff from "./partials/RankStaff.vue";
import RankPromote from "./partials/RankPromote.vue";
import AllStaff from "./partials/AllStaff.vue";
import { ref, watch, computed } from "vue";
import { debouncedWatch } from "@vueuse/core";
import PageTitle from "@/Components/PageTitle.vue";
import PageHeading from "@/Components/PageHeading.vue";
import Modal from "@/Components/NewModal.vue";
import EditRank from "./partials/EditRank.vue";
import { useToggle } from "@vueuse/core";
import DeleteJob from "./partials/DeleteJob.vue";
import { NoSymbolIcon } from "@heroicons/vue/20/solid";
import { ArrowDownTrayIcon } from "@heroicons/vue/24/outline";

import NoPermission from "@/Components/NoPermission.vue";

const page = usePage();
const permissions = computed(() => page.props.value?.auth.permissions);
let props = defineProps({
	job: Object,
	filters: Object,
});
let search = ref(props.filters.search);
debouncedWatch(
	search,
	() => {
		router.get(
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

const selectedStaff = ref([]);
const updateStaffList = (staffList) => {
	selectedStaff.value = staffList;
};

const emit = defineEmits(["addRank", "editRank", "deleteRank"]);
const openEditDialog = ref(false);
const toggleEditModal = useToggle(openEditDialog);

const openConfirmDeleteDialog = ref(false);
const toggleDeleteModal = useToggle(openConfirmDeleteDialog);

const deleteJob = () => {
	router.delete(route("job.delete", { job: props.job.id }));
	toggleDeleteModal();
};
</script>
<template>
	<Head :title="job.name" />
	<MainLayout>
		<main
			v-if="permissions?.includes('view job')"
			class="max-w-7xl mx-auto sm:px-6 lg:px-8 pt-8"
		>
			<PageHeading
				:name="job.name"
				:search="search"
				@searchStaff="(searchValue) => startSearch(searchValue)"
			/>
			<div class="flex gap-4 justify-end pt-4 sm:ml-16 sm:mt-0 sm:flex-none">
				<a
					v-if="permissions?.includes('view job')"
					class="ml-auto flex items-center gap-x-1 rounded-md bg-green-600 dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600"
					:href="
						route('rank-staff.export-rank-promote', { rank: props.job.id })
					"
				>
					<ArrowDownTrayIcon class="-ml-1.5 h-5 w-5" aria-hidden="true" />
					Download promotion list
				</a>
				<button
					v-if="permissions?.includes('edit job')"
					type="button"
					class="block rounded-md bg-green-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600"
					@click="toggleEditModal()"
				>
					Edit rank
				</button>

				<button
					v-if="permissions?.includes('delete job')"
					type="button"
					class="block rounded-md bg-rose-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-rose-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-rose-900"
					@click="toggleDeleteModal()"
				>
					Delete rank
				</button>
			</div>
			<PageTitle
				:tabs="tabs"
				:current="components[currentTab.component]"
				@tab-clicked="(tab) => changeTab(tab)"
			/>
			<component
				:is="components[currentTab.component]"
				v-bind="{ rank: job.id, search, staffList: selectedStaff }"
				@updateStaffList="(staffList) => updateStaffList(staffList)"
			/>
		</main>
		<!-- <NoPermission v-else /> -->
		<Modal :show="openEditDialog" @close="toggleEditModal()">
			<EditRank :job="job" @formSubmitted="toggleEditModal()" />
		</Modal>
		<Modal :show="openConfirmDeleteDialog" @close="toggleDeleteModal()">
			<DeleteJob
				:job="job"
				@close="toggleDeleteModal()"
				@delete-confirmed="deleteJob()"
			/>
		</Modal>
	</MainLayout>
</template>
