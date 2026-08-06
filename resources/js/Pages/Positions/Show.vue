<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, Link } from "@inertiajs/vue3";
import { router } from "@inertiajs/vue3";
import PositionOverview from "./partials/PositionOverview.vue";
// import RankStaff from "./partials/RankStaff.vue";
// import RankPromote from "./partials/RankPromote.vue";
// import AllStaff from "./partials/AllStaff.vue";
import { ref, watch } from "vue";
import { debouncedWatch } from "@vueuse/core";
import PageTitle from "@/Components/PageTitle.vue";
import PageHeading from "@/Components/PageHeading.vue";
import Modal from "@/Components/NewModal.vue";
import EditPositionForm from "./partials/EditPositionForm.vue";
import PositionRolesForm from "./partials/PositionRolesForm.vue";
import { useToggle } from "@vueuse/core";
import DeletePosition from "./Delete.vue";
import { usePage } from "@inertiajs/vue3";
import { computed } from "vue";
let props = defineProps({
	position: Object,
	filters: Object,
});
let search = ref(props.filters.search);
debouncedWatch(
	search,
	() => {
		router.get(
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

const openRolesDialog = ref(false);
const toggleRolesModal = useToggle(openRolesDialog);

const page = usePage();
const permissions = computed(() => page.props?.auth.permissions);
const canUpdate = computed(() => permissions.value?.includes("update position"));
const canDelete = computed(() => permissions.value?.includes("delete position"));
const canManageRoles = computed(() =>
	permissions.value?.includes("manage position roles"),
);

const deletePosition = () => {
	router.delete(route("position.delete", { position: props.position.id }));
};
</script>
<template>
	<Head :title="position.name" />
	<MainLayout>
		<main class="max-w-7xl mx-auto sm:px-6 lg:px-8 pt-8">
			<PageHeading
				:name="position.name"
				:search="search"
				@searchStaff="(searchValue) => startSearch(searchValue)"
			/>
			<div class="flex gap-4 justify-end pt-4 sm:ml-16 sm:mt-0 sm:flex-none">
				<button
					v-if="canManageRoles"
					type="button"
					class="block rounded-md bg-white dark:bg-gray-700 px-3 py-2 text-center text-sm font-semibold text-green-700 dark:text-green-300 shadow-sm ring-1 ring-inset ring-green-600/30 hover:bg-green-50 dark:hover:bg-gray-600"
					@click="toggleRolesModal()"
				>
					Edit roles
				</button>
				<button
					v-if="canUpdate"
					type="button"
					class="block rounded-md bg-green-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600"
					@click="toggleEditModal()"
				>
					Edit position
				</button>

				<button
					v-if="canDelete"
					type="button"
					class="block rounded-md bg-rose-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-rose-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-rose-900"
					@click="toggleDeleteModal()"
				>
					Delete position
				</button>
			</div>
			<section
				class="mt-4 rounded-2xl border border-green-200/60 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm"
			>
				<h2
					class="text-sm font-semibold text-gray-900 dark:text-gray-50 tracking-wide"
				>
					Roles granted by this position
				</h2>
				<div v-if="position.role_names?.length" class="flex flex-wrap gap-2 pt-3">
					<span
						v-for="role in position.role_names"
						:key="role"
						class="rounded-md bg-green-50 dark:bg-gray-700 px-2 py-1 text-xs font-medium text-green-700 dark:text-green-300 ring-1 ring-inset ring-green-600/20"
					>
						{{ role }}
					</span>
				</div>
				<p v-else class="pt-3 text-xs text-gray-500 dark:text-gray-300">
					This position does not grant any role.
				</p>
			</section>
			<PageTitle
				:tabs="tabs"
				:current="components[currentTab.component]"
				@tab-clicked="(tab) => changeTab(tab)"
			/>
			<component
				:is="components[currentTab.component]"
				v-bind="{ staff: position.staff, search, staffList: selectedStaff }"
				class="mt-4"
				@updateStaffList="(staffList) => updateStaffList(staffList)"
			/>
		</main>
		<Modal :show="openEditDialog" @close="toggleEditModal()">
			<EditPositionForm
				:position="position"
				@form-submitted="toggleEditModal()"
			/>
		</Modal>
		<Modal :show="openRolesDialog" @close="toggleRolesModal()">
			<PositionRolesForm
				:position="position"
				@form-submitted="toggleRolesModal()"
			/>
		</Modal>
		<Modal :show="openConfirmDeleteDialog" @close="toggleDeleteModal()">
			<DeletePosition
				@cancel-delete="toggleDeleteModal()"
				@deleted-position="deletePosition()"
			/>
		</Modal>
	</MainLayout>
</template>
