<script setup>
import { ref, computed, reactive, watch } from "vue";
import { router, Link } from "@inertiajs/vue3";
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
		type: Object,
		required: true,
	},
	filterOptions: {
		type: Object,
		required: true,
	},
	filters: {
		type: Object,
		default: () => ({}),
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

const searchQuery = ref(props.filters.search ?? "");
const filterForm = reactive({
	job_category_id: props.filters.job_category_id ?? null,
	rank_id: props.filters.rank_id ?? null,
	sub_unit_id: props.filters.sub_unit_id ?? null,
	gender: props.filters.gender ?? null,
	hire_date_from: props.filters.hire_date_from ?? null,
	hire_date_to: props.filters.hire_date_to ?? null,
	age_from: props.filters.age_from ?? null,
	age_to: props.filters.age_to ?? null,
});

const rows = computed(() => props.staff?.data ?? []);
const meta = computed(() => props.staff?.meta ?? {});

const ranksForCategory = computed(() => {
	const all = props.filterOptions?.ranks ?? [];
	if (!filterForm.job_category_id) return all;
	return all.filter((r) => r.category_id === filterForm.job_category_id);
});

const hasActiveFilters = computed(() =>
	Object.values(filterForm).some((v) => v !== null && v !== ""),
);

function buildParams(page = 1) {
	const params = { page };
	if (searchQuery.value) params.search = searchQuery.value;
	Object.entries(filterForm).forEach(([key, value]) => {
		if (value !== null && value !== "") params[key] = value;
	});
	return params;
}

function reload(page = 1) {
	router.reload({
		only: ["staff", "filter_options", "filters"],
		data: buildParams(page),
		preserveState: true,
		preserveScroll: true,
		replace: true,
	});
}

let searchTimeout = null;
function onSearchInput() {
	clearTimeout(searchTimeout);
	searchTimeout = setTimeout(() => reload(1), 300);
}

watch(
	filterForm,
	() => {
		reload(1);
	},
	{ deep: true },
);

watch(
	() => filterForm.job_category_id,
	() => {
		if (filterForm.rank_id) {
			const stillValid = ranksForCategory.value.some(
				(r) => r.value === filterForm.rank_id,
			);
			if (!stillValid) filterForm.rank_id = null;
		}
	},
);

function resetFilters() {
	Object.keys(filterForm).forEach((key) => {
		filterForm[key] = null;
	});
}

function goToPage(page) {
	if (typeof page !== "number") return;
	if (page < 1 || page > (meta.value.last_page ?? 1)) return;
	reload(page);
}

function prevPage() {
	goToPage((meta.value.current_page ?? 1) - 1);
}

function nextPage() {
	goToPage((meta.value.current_page ?? 1) + 1);
}

const visiblePages = computed(() => {
	const pages = [];
	const total = meta.value.last_page ?? 1;
	const current = meta.value.current_page ?? 1;
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

function exportToExcel() {
	const params = new URLSearchParams();
	Object.entries(buildParams(1)).forEach(([k, v]) => {
		if (k !== "page") params.append(k, v);
	});
	const baseUrl = route("export.unit.staff", { unit: props.unitId });
	const qs = params.toString();
	window.location = qs ? `${baseUrl}?${qs}` : baseUrl;
}
</script>

<template>
	<section>
		<div
			class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-4"
		>
			<h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
				Staff Directory
				<span class="text-sm font-normal text-gray-500 dark:text-gray-400">
					({{ meta.total ?? 0 }})
				</span>
			</h2>

			<div class="flex items-center gap-3">
				<div class="relative">
					<MagnifyingGlassIcon
						class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400"
					/>
					<input
						v-model="searchQuery"
						type="search"
						placeholder="Search staff..."
						class="block w-full sm:w-64 rounded-md border-0 py-2 pl-10 pr-3 text-gray-900 dark:text-gray-100 bg-white dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-600 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-green-600 sm:text-sm sm:leading-6"
						@input="onSearchInput"
					/>
				</div>

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

				<DisclosurePanel class="px-4 pb-4 pt-2">
					<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
						<SearchSelect
							v-model="filterForm.job_category_id"
							label="Job Category"
							placeholder="All Categories"
							:options="filterOptions.job_categories"
						/>
						<SearchSelect
							v-model="filterForm.rank_id"
							label="Rank / Job"
							placeholder="All Ranks"
							:options="ranksForCategory"
							searchable
						/>
						<SearchSelect
							v-if="filterOptions.sub_units.length > 0"
							v-model="filterForm.sub_unit_id"
							label="Sub-Unit"
							placeholder="All Sub-Units"
							:options="filterOptions.sub_units"
							searchable
						/>
						<SearchSelect
							v-model="filterForm.gender"
							label="Gender"
							placeholder="All Genders"
							:options="filterOptions.genders"
						/>
						<SearchDateInput
							v-model="filterForm.hire_date_from"
							label="Hired From"
							placeholder="Start Date"
						/>
						<SearchDateInput
							v-model="filterForm.hire_date_to"
							label="Hired To"
							placeholder="End Date"
							:min="filterForm.hire_date_from"
						/>
						<SearchNumberInput
							v-model="filterForm.age_from"
							label="Age From"
							placeholder="Min Age"
							:min="18"
							:max="100"
						/>
						<SearchNumberInput
							v-model="filterForm.age_to"
							label="Age To"
							placeholder="Max Age"
							:min="filterForm.age_from || 18"
							:max="100"
						/>
					</div>

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
				</DisclosurePanel>
			</div>
		</Disclosure>

		<div
			v-if="rows.length === 0"
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

		<div
			v-else
			class="bg-white dark:bg-gray-800 rounded-lg shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700 overflow-hidden"
		>
			<ul role="list" class="divide-y divide-gray-100 dark:divide-gray-700">
				<li
					v-for="member in rows"
					:key="member.id"
					class="relative flex items-center gap-x-4 px-4 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
				>
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

			<footer
				v-if="(meta.last_page ?? 1) > 1"
				class="bg-white dark:bg-gray-800 px-4 py-3 flex items-center justify-between border-t border-gray-200 dark:border-gray-700 sm:px-6"
			>
				<div class="flex-1 flex justify-between sm:hidden">
					<button
						type="button"
						:disabled="meta.current_page === 1"
						class="relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
						@click="prevPage"
					>
						Previous
					</button>
					<button
						type="button"
						:disabled="meta.current_page === meta.last_page"
						class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
						@click="nextPage"
					>
						Next
					</button>
				</div>
				<div
					class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between"
				>
					<p class="text-sm text-gray-700 dark:text-gray-300">
						Showing
						<span class="font-medium">{{ meta.from ?? 0 }}</span>
						to
						<span class="font-medium">{{ meta.to ?? 0 }}</span>
						of
						<span class="font-medium">{{ meta.total ?? 0 }}</span>
						results
					</p>
					<nav
						class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px"
						aria-label="Pagination"
					>
						<button
							type="button"
							:disabled="meta.current_page === 1"
							class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-500 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
							@click="prevPage"
						>
							<span class="sr-only">Previous</span>
							<ChevronLeftIcon class="h-5 w-5" aria-hidden="true" />
						</button>
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
									page === meta.current_page
										? 'bg-green-100 dark:bg-green-900/30 border-green-500 dark:border-green-600 text-green-600 dark:text-green-400 z-10'
										: 'bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-500 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600'
								"
								@click="goToPage(page)"
							>
								{{ page }}
							</button>
						</template>
						<button
							type="button"
							:disabled="meta.current_page === meta.last_page"
							class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-500 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
							@click="nextPage"
						>
							<span class="sr-only">Next</span>
							<ChevronRightIcon class="h-5 w-5" aria-hidden="true" />
						</button>
					</nav>
				</div>
			</footer>
		</div>
	</section>
</template>
