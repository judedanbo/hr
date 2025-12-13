<script setup>
import { ref, computed, watch, reactive } from "vue";
import { Link } from "@inertiajs/vue3";
import { Disclosure, DisclosureButton, DisclosurePanel } from "@headlessui/vue";
import {
	ArrowDownTrayIcon,
	UsersIcon,
	ChevronLeftIcon,
	ChevronRightIcon,
	FunnelIcon,
	XMarkIcon,
	ChevronDownIcon,
} from "@heroicons/vue/24/outline";
import { MagnifyingGlassIcon } from "@heroicons/vue/20/solid";
import SearchSelect from "@/Components/Forms/SearchSelect.vue";
import SearchDateInput from "@/Components/Forms/SearchDateInput.vue";
import SearchNumberInput from "@/Components/Forms/SearchNumberInput.vue";

const props = defineProps({
	staff: {
		type: Array,
		required: true,
	},
	subs: {
		type: Array,
		default: () => [],
	},
	unitId: {
		type: Number,
		required: true,
	},
	unitName: {
		type: String,
		default: "",
	},
	canDownload: {
		type: Boolean,
		default: false,
	},
});

const searchQuery = ref("");
const currentPage = ref(1);
const perPage = ref(15);

const emit = defineEmits(["search"]);

// Advanced filter state
const filterForm = reactive({
	job_category_id: null,
	rank_id: null,
	sub_unit_id: null,
	gender: null,
	hire_date_from: null,
	hire_date_to: null,
	age_from: null,
	age_to: null,
});

// Extract unique job categories from staff
const jobCategories = computed(() => {
	const categories = new Map();
	props.staff.forEach((member) => {
		if (member.rank?.cat) {
			categories.set(member.rank.cat.id, {
				value: member.rank.cat.id,
				label: member.rank.cat.name,
			});
		}
	});
	return Array.from(categories.values()).sort((a, b) =>
		a.label.localeCompare(b.label),
	);
});

// Extract unique ranks from staff (filtered by category if selected)
const ranks = computed(() => {
	const rankMap = new Map();
	props.staff.forEach((member) => {
		if (member.rank?.id) {
			if (
				!filterForm.job_category_id ||
				member.rank.category_id === filterForm.job_category_id
			) {
				rankMap.set(member.rank.id, {
					value: member.rank.id,
					label: member.rank.name,
					category_id: member.rank.category_id,
				});
			}
		}
	});
	return Array.from(rankMap.values()).sort((a, b) =>
		a.label.localeCompare(b.label),
	);
});

// Sub-units options from subs prop
const subUnits = computed(() => {
	return props.subs.map((sub) => ({
		value: sub.id,
		label: sub.name,
	}));
});

// Gender options (values match GenderEnum: M, F)
const genderOptions = [
	{ value: "M", label: "Male" },
	{ value: "F", label: "Female" },
];

// Filter staff based on search query and advanced filters
const filteredStaff = computed(() => {
	let result = props.staff;

	// Text search
	if (searchQuery.value) {
		const query = searchQuery.value.toLowerCase();
		result = result.filter(
			(member) =>
				member.name?.toLowerCase().includes(query) ||
				member.staff_number?.toLowerCase().includes(query) ||
				member.file_number?.toLowerCase().includes(query) ||
				member.rank?.name?.toLowerCase().includes(query),
		);
	}

	// Job Category filter
	if (filterForm.job_category_id) {
		result = result.filter(
			(member) => member.rank?.category_id === filterForm.job_category_id,
		);
	}

	// Rank filter
	if (filterForm.rank_id) {
		result = result.filter((member) => member.rank?.id === filterForm.rank_id);
	}

	// Sub-unit filter
	if (filterForm.sub_unit_id) {
		result = result.filter(
			(member) => member.unit?.id === filterForm.sub_unit_id,
		);
	}

	// Gender filter
	if (filterForm.gender) {
		result = result.filter((member) => member.gender === filterForm.gender);
	}

	// Hire date range
	if (filterForm.hire_date_from) {
		result = result.filter(
			(member) => member.hire_date_raw >= filterForm.hire_date_from,
		);
	}
	if (filterForm.hire_date_to) {
		result = result.filter(
			(member) => member.hire_date_raw <= filterForm.hire_date_to,
		);
	}

	// Age range (calculate from dob_raw)
	if (filterForm.age_from || filterForm.age_to) {
		const today = new Date();
		result = result.filter((member) => {
			if (!member.dob_raw) return false;
			const birthDate = new Date(member.dob_raw);
			const age = Math.floor(
				(today - birthDate) / (365.25 * 24 * 60 * 60 * 1000),
			);
			if (filterForm.age_from && age < filterForm.age_from) return false;
			if (filterForm.age_to && age > filterForm.age_to) return false;
			return true;
		});
	}

	return result;
});

// Check if any filter is active
const hasActiveFilters = computed(() => {
	return Object.values(filterForm).some((v) => v !== null && v !== "");
});

// Reset all filters
const resetFilters = () => {
	Object.keys(filterForm).forEach((key) => {
		filterForm[key] = null;
	});
};

// Pagination computed properties
const totalPages = computed(() =>
	Math.ceil(filteredStaff.value.length / perPage.value),
);

const paginatedStaff = computed(() => {
	const start = (currentPage.value - 1) * perPage.value;
	const end = start + perPage.value;
	return filteredStaff.value.slice(start, end);
});

const paginationInfo = computed(() => ({
	from:
		filteredStaff.value.length === 0
			? 0
			: (currentPage.value - 1) * perPage.value + 1,
	to: Math.min(currentPage.value * perPage.value, filteredStaff.value.length),
	total: filteredStaff.value.length,
}));

// Generate page numbers to display (limit to 5 pages around current)
const visiblePages = computed(() => {
	const pages = [];
	const total = totalPages.value;
	const current = currentPage.value;

	if (total <= 7) {
		for (let i = 1; i <= total; i++) pages.push(i);
	} else {
		pages.push(1);
		if (current > 3) pages.push("...");

		const start = Math.max(2, current - 1);
		const end = Math.min(total - 1, current + 1);

		for (let i = start; i <= end; i++) pages.push(i);

		if (current < total - 2) pages.push("...");
		pages.push(total);
	}
	return pages;
});

// Reset page when search or filters change
watch(
	[searchQuery, filterForm],
	() => {
		currentPage.value = 1;
	},
	{ deep: true },
);

// Clear rank when category changes if it doesn't belong to the new category
watch(
	() => filterForm.job_category_id,
	() => {
		if (filterForm.rank_id) {
			const selectedRank = ranks.value.find(
				(r) => r.value === filterForm.rank_id,
			);
			if (!selectedRank) {
				filterForm.rank_id = null;
			}
		}
	},
);

// Pagination navigation functions
function goToPage(page) {
	if (typeof page === "number" && page >= 1 && page <= totalPages.value) {
		currentPage.value = page;
	}
}

function prevPage() {
	goToPage(currentPage.value - 1);
}

function nextPage() {
	goToPage(currentPage.value + 1);
}

// Debounced search emit
let searchTimeout = null;
function handleSearch() {
	clearTimeout(searchTimeout);
	searchTimeout = setTimeout(() => {
		emit("search", searchQuery.value);
	}, 300);
}

// Get initials background color based on name
function getInitialsColor(name) {
	const colors = [
		"bg-red-500",
		"bg-orange-500",
		"bg-amber-500",
		"bg-yellow-500",
		"bg-lime-500",
		"bg-green-500",
		"bg-emerald-500",
		"bg-teal-500",
		"bg-cyan-500",
		"bg-sky-500",
		"bg-blue-500",
		"bg-indigo-500",
		"bg-violet-500",
		"bg-purple-500",
		"bg-fuchsia-500",
		"bg-pink-500",
		"bg-rose-500",
	];
	const charCode = (name?.charCodeAt(0) || 0) + (name?.charCodeAt(1) || 0);
	return colors[charCode % colors.length];
}

const exportToExcel = () => {
	const params = new URLSearchParams();

	if (searchQuery.value) {
		params.append("search", searchQuery.value);
	}
	if (filterForm.job_category_id) {
		params.append("job_category_id", filterForm.job_category_id);
	}
	if (filterForm.rank_id) {
		params.append("rank_id", filterForm.rank_id);
	}
	if (filterForm.sub_unit_id) {
		params.append("sub_unit_id", filterForm.sub_unit_id);
	}
	if (filterForm.gender) {
		params.append("gender", filterForm.gender);
	}
	if (filterForm.hire_date_from) {
		params.append("hire_date_from", filterForm.hire_date_from);
	}
	if (filterForm.hire_date_to) {
		params.append("hire_date_to", filterForm.hire_date_to);
	}
	if (filterForm.age_from) {
		params.append("age_from", filterForm.age_from);
	}
	if (filterForm.age_to) {
		params.append("age_to", filterForm.age_to);
	}

	const queryString = params.toString();
	const baseUrl = route("export.unit.staff", { unit: props.unitId });

	window.location = queryString ? `${baseUrl}?${queryString}` : baseUrl;
};
</script>

<template>
	<section>
		<!-- Section Header -->
		<div
			class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-4"
		>
			<h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
				Staff Directory
				<span class="text-sm font-normal text-gray-500 dark:text-gray-400">
					({{ staff.length }})
				</span>
			</h2>

			<div class="flex items-center gap-3">
				<!-- Search -->
				<div class="relative">
					<MagnifyingGlassIcon
						class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400"
					/>
					<input
						v-model="searchQuery"
						type="search"
						placeholder="Search staff..."
						class="block w-full sm:w-64 rounded-md border-0 py-2 pl-10 pr-3 text-gray-900 dark:text-gray-100 bg-white dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-600 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-green-600 sm:text-sm sm:leading-6"
						@input="handleSearch"
					/>
				</div>

				<!-- Export Button -->
				<a
					v-if="canDownload"
					class="inline-flex items-center gap-x-1.5 rounded-md bg-white dark:bg-gray-800 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-gray-100 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer"
					@click.prevent="exportToExcel()"
				>
					<ArrowDownTrayIcon class="-ml-0.5 h-5 w-5 text-gray-400" />
					Export Staff List
				</a>
			</div>
		</div>

		<!-- Advanced Search Panel -->
		<Disclosure v-slot="{ open }" as="div" class="mb-4">
			<div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg">
				<DisclosureButton
					class="flex w-full items-center justify-between px-4 py-3 text-left hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors rounded-lg"
				>
					<div class="flex items-center gap-2">
						<FunnelIcon class="h-5 w-5 text-gray-500 dark:text-gray-400" />
						<span class="text-sm font-medium text-gray-900 dark:text-gray-100">
							Advanced Filters
						</span>
						<span
							v-if="hasActiveFilters"
							class="inline-flex items-center rounded-full bg-green-100 dark:bg-green-900 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:text-green-200"
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
						<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
							<!-- Job Category Filter -->
							<SearchSelect
								v-model="filterForm.job_category_id"
								label="Job Category"
								placeholder="All Categories"
								:options="jobCategories"
							/>

							<!-- Rank Filter -->
							<SearchSelect
								v-model="filterForm.rank_id"
								label="Rank / Job"
								placeholder="All Ranks"
								:options="ranks"
								searchable
							/>

							<!-- Sub-unit Filter (only show if subs exist) -->
							<SearchSelect
								v-if="subs.length > 0"
								v-model="filterForm.sub_unit_id"
								label="Sub-Unit"
								placeholder="All Sub-Units"
								:options="subUnits"
								searchable
							/>

							<!-- Gender Filter -->
							<SearchSelect
								v-model="filterForm.gender"
								label="Gender"
								placeholder="All Genders"
								:options="genderOptions"
							/>

							<!-- Hire Date From -->
							<SearchDateInput
								v-model="filterForm.hire_date_from"
								label="Hired From"
								placeholder="Start Date"
							/>

							<!-- Hire Date To -->
							<SearchDateInput
								v-model="filterForm.hire_date_to"
								label="Hired To"
								placeholder="End Date"
								:min="filterForm.hire_date_from"
							/>

							<!-- Age From -->
							<SearchNumberInput
								v-model="filterForm.age_from"
								label="Age From"
								placeholder="Min Age"
								:min="18"
								:max="100"
							/>

							<!-- Age To -->
							<SearchNumberInput
								v-model="filterForm.age_to"
								label="Age To"
								placeholder="Max Age"
								:min="filterForm.age_from || 18"
								:max="100"
							/>
						</div>

						<!-- Clear Filters Button -->
						<div class="mt-4 flex flex-wrap gap-3">
							<button
								v-if="hasActiveFilters"
								type="button"
								class="inline-flex items-center gap-2 rounded-md bg-white dark:bg-gray-700 px-4 py-2 text-sm font-semibold text-gray-900 dark:text-gray-100 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600"
								@click="resetFilters"
							>
								<XMarkIcon class="h-4 w-4" />
								Clear All Filters
							</button>
						</div>

						<!-- Results count with filters -->
						<p
							v-if="hasActiveFilters"
							class="mt-3 text-sm text-gray-500 dark:text-gray-400"
						>
							Showing {{ filteredStaff.length }} of {{ staff.length }} staff
							members
						</p>
					</DisclosurePanel>
				</transition>
			</div>
		</Disclosure>

		<!-- Empty State -->
		<div
			v-if="filteredStaff.length === 0"
			class="text-center py-12 bg-white dark:bg-gray-800 rounded-lg ring-1 ring-gray-900/5 dark:ring-gray-700"
		>
			<UsersIcon class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" />
			<p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
				{{
					searchQuery || hasActiveFilters
						? "No staff found matching your search criteria."
						: "No staff assigned to this unit."
				}}
			</p>
			<button
				v-if="hasActiveFilters"
				type="button"
				class="mt-3 text-sm font-medium text-green-600 dark:text-green-400 hover:text-green-500"
				@click="resetFilters"
			>
				Clear filters
			</button>
		</div>

		<!-- Staff List -->
		<div
			v-else
			class="bg-white dark:bg-gray-800 rounded-lg shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700 overflow-hidden"
		>
			<ul role="list" class="divide-y divide-gray-100 dark:divide-gray-700">
				<li
					v-for="member in paginatedStaff"
					:key="member.id"
					class="relative flex items-center gap-x-4 px-4 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
				>
					<!-- Avatar -->
					<div class="flex-shrink-0">
						<img
							v-if="member.image"
							:src="member.image"
							:alt="member.name"
							class="h-12 w-12 rounded-full object-cover ring-2 ring-white dark:ring-gray-700"
						/>
						<div
							v-else
							:class="[
								'h-12 w-12 rounded-full flex items-center justify-center text-white font-semibold text-sm',
								getInitialsColor(member.name),
							]"
						>
							{{ member.initials }}
						</div>
					</div>

					<!-- Staff Info -->
					<div class="min-w-0 flex-1">
						<div class="flex items-center gap-x-3">
							<Link
								:href="route('staff.show', { staff: member.id })"
								class="text-sm font-semibold text-gray-900 dark:text-gray-100 hover:text-green-600 dark:hover:text-green-400"
							>
								{{ member.name }}
							</Link>
							<span
								v-if="member.rank?.name"
								class="inline-flex items-center rounded-md bg-green-50 dark:bg-green-900/30 px-2 py-1 text-xs font-medium text-green-700 dark:text-green-400 ring-1 ring-inset ring-green-600/20 dark:ring-green-500/30"
							>
								{{ member.rank.name }}
							</span>
						</div>
						<div
							class="mt-1 flex flex-wrap items-center gap-x-4 text-xs text-gray-500 dark:text-gray-400"
						>
							<span v-if="member.staff_number">
								Staff #: {{ member.staff_number }}
							</span>
							<span v-if="member.file_number">
								File #: {{ member.file_number }}
							</span>
						</div>
					</div>

					<!-- Right side info -->
					<div
						class="hidden sm:flex flex-col items-end gap-1 text-xs text-gray-500 dark:text-gray-400"
					>
						<span v-if="member.hire_date"> Hired: {{ member.hire_date }} </span>
						<span v-if="member.rank?.start_date">
							Rank since: {{ member.rank.start_date }}
						</span>
					</div>
				</li>
			</ul>

			<!-- Pagination Footer -->
			<footer
				v-if="totalPages > 1"
				class="bg-white dark:bg-gray-800 px-4 py-3 flex items-center justify-between border-t border-gray-200 dark:border-gray-700 sm:px-6"
			>
				<!-- Mobile pagination -->
				<div class="flex-1 flex justify-between sm:hidden">
					<button
						type="button"
						:disabled="currentPage === 1"
						class="relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
						@click="prevPage"
					>
						Previous
					</button>
					<button
						type="button"
						:disabled="currentPage === totalPages"
						class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
						@click="nextPage"
					>
						Next
					</button>
				</div>

				<!-- Desktop pagination -->
				<div
					class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between"
				>
					<div>
						<p class="text-sm text-gray-700 dark:text-gray-300">
							Showing
							<span class="font-medium">{{ paginationInfo.from }}</span>
							to
							<span class="font-medium">{{ paginationInfo.to }}</span>
							of
							<span class="font-medium">{{ paginationInfo.total }}</span>
							results
						</p>
					</div>
					<div>
						<nav
							class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px"
							aria-label="Pagination"
						>
							<!-- Previous button -->
							<button
								type="button"
								:disabled="currentPage === 1"
								class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-500 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
								@click="prevPage"
							>
								<span class="sr-only">Previous</span>
								<ChevronLeftIcon class="h-5 w-5" aria-hidden="true" />
							</button>

							<!-- Page numbers -->
							<template v-for="(page, index) in visiblePages" :key="index">
								<span
									v-if="page === '...'"
									class="relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-500 dark:text-gray-400"
								>
									...
								</span>
								<button
									v-else
									type="button"
									class="relative inline-flex items-center px-4 py-2 border text-sm font-medium cursor-pointer"
									:class="
										page === currentPage
											? 'bg-green-100 dark:bg-green-900/30 border-green-500 dark:border-green-600 text-green-600 dark:text-green-400 z-10'
											: 'bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-500 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600'
									"
									@click="goToPage(page)"
								>
									{{ page }}
								</button>
							</template>

							<!-- Next button -->
							<button
								type="button"
								:disabled="currentPage === totalPages"
								class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-500 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
								@click="nextPage"
							>
								<span class="sr-only">Next</span>
								<ChevronRightIcon class="h-5 w-5" aria-hidden="true" />
							</button>
						</nav>
					</div>
				</div>
			</footer>
		</div>
	</section>
</template>
