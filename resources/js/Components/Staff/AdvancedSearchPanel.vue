<script setup>
import { ref, reactive, watch, computed } from "vue";
import { Disclosure, DisclosureButton, DisclosurePanel } from "@headlessui/vue";
import {
	ChevronDownIcon,
	FunnelIcon,
	XMarkIcon,
} from "@heroicons/vue/24/outline";
import SearchSelect from "@/Components/Forms/SearchSelect.vue";
import SearchDateInput from "@/Components/Forms/SearchDateInput.vue";
import SearchNumberInput from "@/Components/Forms/SearchNumberInput.vue";

const props = defineProps({
	filters: {
		type: Object,
		default: () => ({}),
	},
	filterOptions: {
		type: Object,
		default: () => ({
			jobCategories: [],
			jobs: [],
			units: [],
			departments: [],
			statuses: [],
			genders: [],
		}),
	},
	isLoading: {
		type: Boolean,
		default: false,
	},
	isSearching: {
		type: Boolean,
		default: false,
	},
});

const emit = defineEmits(["search", "clear"]);

const searchForm = reactive({
	rank_id: props.filters?.rank_id ? parseInt(props.filters.rank_id) : null,
	job_category_id: props.filters?.job_category_id
		? parseInt(props.filters.job_category_id)
		: null,
	unit_id: props.filters?.unit_id ? parseInt(props.filters.unit_id) : null,
	department_id: props.filters?.department_id
		? parseInt(props.filters.department_id)
		: null,
	gender: props.filters?.gender || null,
	status: props.filters?.status || null,
	hire_date_from: props.filters?.hire_date_from || null,
	hire_date_to: props.filters?.hire_date_to || null,
	age_from: props.filters?.age_from ? parseInt(props.filters.age_from) : null,
	age_to: props.filters?.age_to ? parseInt(props.filters.age_to) : null,
});

const hasActiveFilters = ref(false);

// Filter jobs based on selected category
const filteredJobs = computed(() => {
	if (!searchForm.job_category_id) {
		return props.filterOptions.jobs;
	}
	return props.filterOptions.jobs.filter(
		(job) => job.category_id === searchForm.job_category_id,
	);
});

// Filter units based on selected department
const filteredUnits = computed(() => {
	if (!searchForm.department_id) {
		return props.filterOptions.units;
	}
	return props.filterOptions.units.filter(
		(unit) => unit.department_id === searchForm.department_id,
	);
});

const checkActiveFilters = () => {
	hasActiveFilters.value = Object.values(searchForm).some(
		(value) => value !== null && value !== "",
	);
};

// Watch for changes in searchForm
watch(searchForm, checkActiveFilters, { deep: true });

// Watch for category changes to clear rank selection if needed
watch(
	() => searchForm.job_category_id,
	(newCategoryId) => {
		// Clear rank_id if the selected rank doesn't belong to the new category
		if (searchForm.rank_id && newCategoryId) {
			const selectedRank = props.filterOptions.jobs.find(
				(job) => job.value === searchForm.rank_id,
			);
			if (selectedRank && selectedRank.category_id !== newCategoryId) {
				searchForm.rank_id = null;
			}
		}
	},
);

// Watch for department changes to clear unit selection if needed
watch(
	() => searchForm.department_id,
	(newDepartmentId) => {
		// Clear unit_id if the selected unit doesn't belong to the new department
		if (searchForm.unit_id && newDepartmentId) {
			const selectedUnit = props.filterOptions.units.find(
				(unit) => unit.value === searchForm.unit_id,
			);
			if (selectedUnit && selectedUnit.department_id !== newDepartmentId) {
				searchForm.unit_id = null;
			}
		}
	},
);

// Watch for changes in props.filters to update searchForm
watch(
	() => props.filters,
	(newFilters) => {
		searchForm.rank_id = newFilters?.rank_id
			? parseInt(newFilters.rank_id)
			: null;
		searchForm.job_category_id = newFilters?.job_category_id
			? parseInt(newFilters.job_category_id)
			: null;
		searchForm.unit_id = newFilters?.unit_id
			? parseInt(newFilters.unit_id)
			: null;
		searchForm.department_id = newFilters?.department_id
			? parseInt(newFilters.department_id)
			: null;
		searchForm.gender = newFilters?.gender || null;
		searchForm.status = newFilters?.status || null;
		searchForm.hire_date_from = newFilters?.hire_date_from || null;
		searchForm.hire_date_to = newFilters?.hire_date_to || null;
		searchForm.age_from = newFilters?.age_from
			? parseInt(newFilters.age_from)
			: null;
		searchForm.age_to = newFilters?.age_to ? parseInt(newFilters.age_to) : null;
	},
	{ deep: true },
);

checkActiveFilters();

const handleSearch = () => {
	emit("search", { ...searchForm });
};

const resetFilters = () => {
	Object.keys(searchForm).forEach((key) => {
		searchForm[key] = null;
	});
	emit("clear");
};
</script>

<template>
	<Disclosure v-slot="{ open }" as="div" class="mb-4">
		<div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg">
			<DisclosureButton
				dusk="advanced-search-toggle"
				class="flex w-full items-center justify-between px-4 py-3 text-left hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
			>
				<div class="flex items-center gap-2">
					<FunnelIcon class="h-5 w-5 text-gray-500 dark:text-gray-400" />
					<span class="text-sm font-medium text-gray-900 dark:text-gray-100">
						Advanced Search
					</span>
					<span
						v-if="hasActiveFilters"
						class="inline-flex items-center rounded-full bg-indigo-100 dark:bg-indigo-900 px-2.5 py-0.5 text-xs font-medium text-indigo-800 dark:text-indigo-200"
					>
						Active
					</span>
				</div>
				<ChevronDownIcon
					:class="[
						open ? 'rotate-180 transform' : '',
						'h-5 w-5 text-gray-500 dark:text-gray-400 transition-transform',
					]"
				/>
			</DisclosureButton>

			<transition
				enter-active-class="transition duration-200 ease-out"
				enter-from-class="transform scale-95 opacity-0"
				enter-to-class="transform scale-100 opacity-100"
				leave-active-class="transition duration-100 ease-in"
				leave-from-class="transform scale-100 opacity-100"
				leave-to-class="transform scale-95 opacity-0"
			>
				<DisclosurePanel class="px-4 pb-4 pt-2">
					<form @submit.prevent="handleSearch">
						<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
							<!-- Job Category Filter -->
							<SearchSelect
								v-model="searchForm.job_category_id"
								label="Job Category"
								placeholder="All Categories"
								:options="filterOptions.jobCategories"
							/>

							<!-- Rank Filter -->

							<SearchSelect
								v-model="searchForm.rank_id"
								label="Rank / Job"
								placeholder="All Ranks"
								:options="filteredJobs"
								searchable
							/>
							<!-- Department Filter -->
							<SearchSelect
								v-model="searchForm.department_id"
								label="Department"
								placeholder="All Departments"
								:options="filterOptions.departments"
							/>

							<!-- Unit Filter -->
							<SearchSelect
								v-model="searchForm.unit_id"
								label="Unit"
								placeholder="All Units"
								:options="filteredUnits"
								searchable
							/>

							<!-- Gender Filter -->
							<SearchSelect
								v-model="searchForm.gender"
								label="Gender"
								placeholder="All Genders"
								:options="filterOptions.genders"
							/>

							<!-- Hire Date From -->
							<SearchDateInput
								v-model="searchForm.hire_date_from"
								label="Hired From"
								placeholder="Start Date"
							/>

							<!-- Hire Date To -->
							<SearchDateInput
								v-model="searchForm.hire_date_to"
								label="Hired To"
								placeholder="End Date"
								:min="searchForm.hire_date_from"
							/>

							<!-- Age From -->
							<SearchNumberInput
								v-model="searchForm.age_from"
								label="Age From"
								placeholder="Min Age"
								:min="18"
								:max="100"
							/>

							<!-- Age To -->
							<SearchNumberInput
								v-model="searchForm.age_to"
								label="Age To"
								placeholder="Max Age"
								:min="searchForm.age_from || 18"
								:max="100"
							/>
						</div>

						<!-- Action Buttons -->
						<div class="mt-4 flex flex-wrap gap-3">
							<button
								type="submit"
								:disabled="isLoading || isSearching"
								class="inline-flex items-center gap-2 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:opacity-50 disabled:cursor-not-allowed"
							>
								<svg
									v-if="isSearching"
									class="animate-spin h-4 w-4 text-white"
									xmlns="http://www.w3.org/2000/svg"
									fill="none"
									viewBox="0 0 24 24"
								>
									<circle
										class="opacity-25"
										cx="12"
										cy="12"
										r="10"
										stroke="currentColor"
										stroke-width="4"
									></circle>
									<path
										class="opacity-75"
										fill="currentColor"
										d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
									></path>
								</svg>
								<FunnelIcon v-else class="h-4 w-4" />
								{{ isSearching ? "Searching..." : "Apply Filters" }}
							</button>
							<button
								v-if="hasActiveFilters"
								type="button"
								:disabled="isSearching"
								class="inline-flex items-center gap-2 rounded-md bg-white dark:bg-gray-700 px-4 py-2 text-sm font-semibold text-gray-900 dark:text-gray-100 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
								@click="resetFilters"
							>
								<XMarkIcon class="h-4 w-4" />
								Clear All Filters
							</button>
						</div>
						<p
							v-if="isLoading"
							class="mt-2 text-sm text-gray-500 dark:text-gray-400"
						>
							Loading filter options...
						</p>
					</form>
				</DisclosurePanel>
			</transition>
		</div>
	</Disclosure>
</template>
