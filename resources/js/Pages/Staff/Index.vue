<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, usePage } from "@inertiajs/vue3";
import { ref, computed, onMounted } from "vue";
import { router } from "@inertiajs/vue3";
import Pagination from "../../Components/Pagination.vue";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import Modal from "@/Components/NewModal.vue";
import AddStaffForm from "./AddStaffForm.vue";
import AdvancedSearchPanel from "@/Components/Staff/AdvancedSearchPanel.vue";
import ActiveFilters from "@/Components/Staff/ActiveFilters.vue";
import { useToggle } from "@vueuse/core";
import TableHeader from "./partials/TableHeader.vue";
import StaffList from "./partials/StaffList.vue";
import { useNavigation } from "@/Composables/navigation";
import { useSearch } from "@/Composables/search";
import { Link } from "@inertiajs/vue3";
import { ArrowDownTrayIcon } from "@heroicons/vue/24/outline";

const navigation = computed(() => useNavigation(props.staff));

const page = usePage();
const permissions = computed(() => page.props?.auth.permissions);

let props = defineProps({
	staff: { type: Object, required: true },
	filters: { type: Object, default: () => {} },
});

let openDialog = ref(false);
let filterOptions = ref({
	jobCategories: [],
	jobs: [],
	units: [],
	departments: [],
	statuses: [],
	genders: [],
});
let isLoadingFilters = ref(false);
let isSearching = ref(false);

let toggle = useToggle(openDialog);

const searchStaff = (value) => {
	useSearch(value, route("staff.index"));
};

const activeFilterCount = computed(() => {
	const filterKeys = [
		"rank_id",
		"job_category_id",
		"unit_id",
		"department_id",
		"gender",
		"status",
		"hire_date_from",
		"hire_date_to",
		"age_from",
		"age_to",
	];
	return filterKeys.filter((key) => props.filters[key]).length;
});

const cleanFilters = (filters) => {
	return Object.fromEntries(
		Object.entries(filters).filter(
			([_, value]) => value !== null && value !== undefined && value !== "",
		),
	);
};

const handleAdvancedSearch = (filters) => {
	isSearching.value = true;
	const cleanedFilters = cleanFilters({
		...filters,
		search: props.filters.search,
	});
	router.get(route("staff.index"), cleanedFilters, {
		preserveState: true,
		preserveScroll: true,
		onFinish: () => {
			isSearching.value = false;
		},
	});
};

const clearAdvancedFilters = () => {
	const cleanedFilters = cleanFilters({ search: props.filters.search });
	router.get(route("staff.index"), cleanedFilters, {
		preserveState: true,
		preserveScroll: true,
	});
};

const removeFilter = (keysToRemove) => {
	const currentFilters = { ...props.filters };

	// Remove the specified filter keys
	keysToRemove.forEach((key) => {
		delete currentFilters[key];
	});

	const cleanedFilters = cleanFilters(currentFilters);
	router.get(route("staff.index"), cleanedFilters, {
		preserveState: true,
		preserveScroll: true,
	});
};

let openStaff = (staff) => {
	router.visit(route("staff.show", { staff: staff }));
};

let BreadCrumpLinks = [
	{
		name: "Staff",
		url: "",
	},
];

onMounted(async () => {
	isLoadingFilters.value = true;
	try {
		const response = await window.axios.get("/staff-search/options");
		filterOptions.value = response.data;
	} catch (error) {
		console.error("Failed to load filter options:", error);
	} finally {
		isLoadingFilters.value = false;
	}
});
</script>

<template>
	<MainLayout>
		<Head title="Staff" />
		<main class="max-w-7xl mx-auto sm:px-6 lg:px-8">
			<BreadCrumpVue :links="BreadCrumpLinks" />
			<div class="overflow-hidden shadow-sm sm:rounded-lg px-6">
				<TableHeader
					title="Staff"
					:total="staff.total"
					:search="filters.search"
					action-text="Onboard Staff"
					:action-permission="permissions?.includes('create staff')"
					@action-clicked="toggle()"
					@search-entered="(value) => searchStaff(value)"
				/>

				<AdvancedSearchPanel
					:filters="filters"
					:filter-options="filterOptions"
					:is-loading="isLoadingFilters"
					:is-searching="isSearching"
					@search="handleAdvancedSearch"
					@clear="clearAdvancedFilters"
				/>

				<ActiveFilters
					:filters="filters"
					:filter-options="filterOptions"
					@remove-filter="removeFilter"
					@clear-all="clearAdvancedFilters"
				/>

				<!-- Result Summary -->
				<div v-if="activeFilterCount > 0 || filters.search" class="mb-4">
					<div
						class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400"
					>
						<svg
							class="h-5 w-5 text-indigo-500"
							fill="none"
							viewBox="0 0 24 24"
							stroke="currentColor"
						>
							<path
								stroke-linecap="round"
								stroke-linejoin="round"
								stroke-width="2"
								d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
							/>
						</svg>
						<span class="font-medium">
							Showing {{ staff.total.toLocaleString() }} staff member{{
								staff.total !== 1 ? "s" : ""
							}}
						</span>
						<span v-if="activeFilterCount > 0" class="text-gray-500">
							({{ activeFilterCount }} filter{{
								activeFilterCount !== 1 ? "s" : ""
							}}
							applied)
						</span>
					</div>
				</div>

				<div
					v-if="
						permissions?.includes('download active staff data') ||
						permissions?.includes('download separated staff data')
					"
					class="flex gap-x-5"
				>
					<a
						class="rounded-md flex gap-x-3 bg-green-600 dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600"
						:href="route('report.staff')"
					>
						<arrow-down-tray-icon class="-ml-1.5 h-5 w-5" aria-hidden="true" />
						Staff position
					</a>
					<a
						class="rounded-md flex gap-x-3 bg-green-600 dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600"
						:href="route('report.staff-details')"
					>
						<arrow-down-tray-icon class="-ml-1.5 h-5 w-5" aria-hidden="true" />
						Staff details
					</a>
					<a
						class="rounded-md flex gap-x-3 bg-green-600 dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600"
						:href="route('report.staff-retirement')"
					>
						<arrow-down-tray-icon class="-ml-1.5 h-5 w-5" aria-hidden="true" />
						Staff to retire
					</a>
					<a
						class="rounded-md flex gap-x-3 bg-green-600 dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600"
						:href="route('report.staff-pending-transfer')"
					>
						<arrow-down-tray-icon class="-ml-1.5 h-5 w-5" aria-hidden="true" />
						Pending Transfer
					</a>
					<a
						class="rounded-md flex gap-x-3 bg-green-600 dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600"
						:href="route('report.staff-positions')"
					>
						<arrow-down-tray-icon class="-ml-1.5 h-5 w-5" aria-hidden="true" />
						Positions
					</a>
					<a
						class="rounded-md flex gap-x-3 bg-green-600 dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600"
						:href="route('staff-list')"
					>
						<arrow-down-tray-icon class="-ml-1.5 h-5 w-5" aria-hidden="true" />
						Staff Email
					</a>
				</div>

				<StaffList
					:staff="staff.data"
					@open-staff="(staffId) => openStaff(staffId)"
				>
					<template #pagination>
						<Pagination :navigation="navigation" />
					</template>
				</StaffList>
			</div>
		</main>
		<Modal :show="openDialog" @close="toggle()">
			<AddStaffForm @form-submitted="toggle()" />
		</Modal>
	</MainLayout>
</template>
