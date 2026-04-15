<script setup>
import { ref, computed, watch } from "vue";
import { router, Head, usePage } from "@inertiajs/vue3";
import { debouncedWatch } from "@vueuse/core";
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import Pagination from "@/Components/Pagination.vue";
import ExpandableChart from "@/Components/Charts/Qualifications/ExpandableChart.vue";
import { Menu, MenuButton, MenuItems, MenuItem } from "@headlessui/vue";
import { ChevronDownIcon } from "@heroicons/vue/24/outline";
import LevelDistributionChart from "@/Components/Charts/Qualifications/LevelDistributionChart.vue";
import ByUnitChart from "@/Components/Charts/Qualifications/ByUnitChart.vue";
import TopInstitutionsChart from "@/Components/Charts/Qualifications/TopInstitutionsChart.vue";
import TopQualificationsChart from "@/Components/Charts/Qualifications/TopQualificationsChart.vue";
import LevelByGenderChart from "@/Components/Charts/Qualifications/LevelByGenderChart.vue";
import AcquiredOverTimeChart from "@/Components/Charts/Qualifications/AcquiredOverTimeChart.vue";

const props = defineProps({
	filters: Object,
	filterOptions: Object,
	kpis: Object,
	levelDistribution: Object,
	byUnit: Object,
	topInstitutions: Array,
	topQualifications: Array,
	levelByGender: Object,
	trendByYear: Object,
	staffList: Object,
});

const page = usePage();

const form = ref({
	department_id: props.filters?.department_id ?? "",
	unit_id: props.filters?.unit_id ?? "",
	level: props.filters?.level ?? "",
	status: props.filters?.status ?? "",
	gender: props.filters?.gender ?? "",
	year_from: props.filters?.year_from ?? "",
	year_to: props.filters?.year_to ?? "",
	institution: props.filters?.institution ?? "",
	course: props.filters?.course ?? "",
});

// Units filtered by the currently selected department.
const availableUnits = computed(() => {
	const all = props.filterOptions?.units ?? [];
	if (!form.value.department_id) return all;
	const dep = Number(form.value.department_id);
	return all.filter((u) => u.department_id === dep);
});

// If department changes and the current unit isn't under that department, clear it.
watch(
	() => form.value.department_id,
	() => {
		if (!form.value.unit_id) return;
		const unitId = Number(form.value.unit_id);
		const stillValid = availableUnits.value.some((u) => u.id === unitId);
		if (!stillValid) form.value.unit_id = "";
	},
);

const levelLabels = computed(() => {
	const m = {};
	(props.filterOptions?.levels ?? []).forEach((l) => {
		m[l.value] = l.label;
	});
	return m;
});

const canExport = computed(() => {
	const perms = page.props.auth?.permissions ?? [];
	return (
		Array.isArray(perms) && perms.includes("qualifications.reports.export")
	);
});

const breadcrumbs = [
	{ name: "Reports", url: "/reports" },
	{ name: "Qualifications", url: "" },
];

const statusLabels = computed(() => {
	const m = {};
	(props.filterOptions?.statuses ?? []).forEach((s) => {
		m[s.value] = s.label;
	});
	return m;
});

const departmentById = computed(() => {
	const m = {};
	(props.filterOptions?.departments ?? []).forEach((d) => {
		m[d.id] = d.name;
	});
	return m;
});

const unitById = computed(() => {
	const m = {};
	(props.filterOptions?.units ?? []).forEach((u) => {
		m[u.id] = u.name;
	});
	return m;
});

const genderLabels = { M: "Male", F: "Female" };

const activeFilters = computed(() => {
	const f = form.value;
	const out = [];
	if (f.department_id)
		out.push({
			key: "department_id",
			label: "Department",
			value: departmentById.value[f.department_id] ?? f.department_id,
		});
	if (f.unit_id)
		out.push({
			key: "unit_id",
			label: "Unit",
			value: unitById.value[f.unit_id] ?? f.unit_id,
		});
	if (f.level)
		out.push({
			key: "level",
			label: "Level",
			value: levelLabels.value[f.level] ?? f.level,
		});
	if (f.status)
		out.push({
			key: "status",
			label: "Status",
			value: statusLabels.value[f.status] ?? f.status,
		});
	if (f.gender)
		out.push({
			key: "gender",
			label: "Gender",
			value: genderLabels[f.gender] ?? f.gender,
		});
	if (f.year_from)
		out.push({ key: "year_from", label: "Year from", value: f.year_from });
	if (f.year_to)
		out.push({ key: "year_to", label: "Year to", value: f.year_to });
	if (f.institution)
		out.push({ key: "institution", label: "Institution", value: f.institution });
	if (f.course)
		out.push({ key: "course", label: "Course", value: f.course });
	return out;
});

function removeFilter(key) {
	form.value[key] = "";
}

function cleanParams() {
	const out = {};
	for (const [k, v] of Object.entries(form.value)) {
		if (v !== null && v !== undefined && v !== "") out[k] = v;
	}
	return out;
}

debouncedWatch(
	form,
	() => {
		router.get(route("qualifications.reports.index"), cleanParams(), {
			preserveState: true,
			preserveScroll: true,
			replace: true,
		});
	},
	{ deep: true, debounce: 300 },
);

function clearFilters() {
	form.value = {
		department_id: "",
		unit_id: "",
		level: "",
		status: "",
		gender: "",
		year_from: "",
		year_to: "",
		institution: "",
		course: "",
	};
}

function exportUrl(format, type) {
	const base =
		format === "pdf"
			? route("qualifications.reports.export.pdf")
			: route("qualifications.reports.export.excel");
	const params = new URLSearchParams({ ...cleanParams(), type });
	return `${base}?${params.toString()}`;
}

const reportTypes = [
	{ value: "list", label: "Staff List" },
	{ value: "by_unit", label: "By Unit" },
	{ value: "by_level", label: "By Level" },
	{ value: "gaps", label: "Staff Without Quals" },
];
</script>

<template>
	<Head title="Qualification Reports" />

	<MainLayout>
		<template #header>
			<h2
				class="font-semibold text-xl text-gray-800 leading-tight dark:text-gray-50"
			>
				Qualification Reports
			</h2>
		</template>

		<div class="max-w-7xl mx-auto sm:px-6 lg:px-8 py-6 space-y-6">
			<BreadCrumpVue :links="breadcrumbs" />

			<div v-if="canExport" class="flex justify-end gap-2">
				<Menu as="div" class="relative">
					<MenuButton
						class="inline-flex items-center gap-1 px-3 py-1.5 text-sm bg-indigo-600 text-white rounded hover:bg-indigo-700"
					>
						Export PDF
						<ChevronDownIcon class="h-4 w-4" />
					</MenuButton>
					<MenuItems
						class="absolute right-0 mt-1 min-w-[180px] origin-top-right rounded-md bg-white dark:bg-gray-800 shadow-lg border border-gray-200 dark:border-gray-700 focus:outline-none z-20"
					>
						<MenuItem
							v-for="rt in reportTypes"
							:key="'pdf-' + rt.value"
							v-slot="{ active }"
						>
							<a
								:href="exportUrl('pdf', rt.value)"
								class="block px-3 py-2 text-sm text-gray-800 dark:text-gray-100"
								:class="active ? 'bg-gray-100 dark:bg-gray-700' : ''"
							>
								{{ rt.label }}
							</a>
						</MenuItem>
					</MenuItems>
				</Menu>
				<Menu as="div" class="relative">
					<MenuButton
						class="inline-flex items-center gap-1 px-3 py-1.5 text-sm bg-emerald-600 text-white rounded hover:bg-emerald-700"
					>
						Export Excel
						<ChevronDownIcon class="h-4 w-4" />
					</MenuButton>
					<MenuItems
						class="absolute right-0 mt-1 min-w-[180px] origin-top-right rounded-md bg-white dark:bg-gray-800 shadow-lg border border-gray-200 dark:border-gray-700 focus:outline-none z-20"
					>
						<MenuItem
							v-for="rt in reportTypes"
							:key="'excel-' + rt.value"
							v-slot="{ active }"
						>
							<a
								:href="exportUrl('excel', rt.value)"
								class="block px-3 py-2 text-sm text-gray-800 dark:text-gray-100"
								:class="active ? 'bg-gray-100 dark:bg-gray-700' : ''"
							>
								{{ rt.label }}
							</a>
						</MenuItem>
					</MenuItems>
				</Menu>
			</div>

			<div
				class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700"
			>
				<div class="grid grid-cols-1 md:grid-cols-4 gap-3">
					<select
						v-model="form.department_id"
						class="rounded border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm"
					>
						<option value="">All Departments</option>
						<option
							v-for="d in filterOptions?.departments ?? []"
							:key="d.id"
							:value="d.id"
						>
							{{ d.name }}
						</option>
					</select>
					<select
						v-model="form.unit_id"
						class="rounded border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm"
					>
						<option value="">
							{{ form.department_id ? "All Units in Department" : "All Units" }}
						</option>
						<option v-for="u in availableUnits" :key="u.id" :value="u.id">
							{{ u.name }}
						</option>
					</select>
					<select
						v-model="form.level"
						class="rounded border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm"
					>
						<option value="">All Levels</option>
						<option
							v-for="l in filterOptions?.levels ?? []"
							:key="l.value"
							:value="l.value"
						>
							{{ l.label }}
						</option>
					</select>
					<select
						v-model="form.status"
						class="rounded border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm"
					>
						<option value="">All Statuses</option>
						<option
							v-for="s in filterOptions?.statuses ?? []"
							:key="s.value"
							:value="s.value"
						>
							{{ s.label }}
						</option>
					</select>
					<select
						v-model="form.gender"
						class="rounded border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm"
					>
						<option value="">Any Gender</option>
						<option value="M">Male</option>
						<option value="F">Female</option>
					</select>
					<input
						v-model="form.year_from"
						type="number"
						placeholder="Year from"
						class="rounded border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm"
					/>
					<input
						v-model="form.year_to"
						type="number"
						placeholder="Year to"
						class="rounded border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm"
					/>
				</div>
				<div class="flex justify-around flex-grow mt-3 gap-5">
					<input
						v-model="form.institution"
						type="text"
						placeholder="Institution"
						class="rounded border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm w-full"
					/>
					<input
						v-model="form.course"
						type="text"
						placeholder="Course keyword"
						class="rounded border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm w-full"
					/>
				</div>
				<div class="mt-3 flex justify-end">
					<button
						type="button"
						@click="clearFilters"
						class="text-sm text-gray-600 dark:text-gray-300 hover:underline"
					>
						Clear all
					</button>
				</div>
			</div>

			<div v-if="activeFilters.length" class="flex flex-wrap items-center gap-2">
				<span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">
					Active filters:
				</span>
				<span
					v-for="f in activeFilters"
					:key="f.key"
					class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-200 text-xs"
				>
					<span class="font-medium">{{ f.label }}:</span>
					<span>{{ f.value }}</span>
					<button
						type="button"
						class="ml-0.5 rounded-full hover:bg-indigo-100 dark:hover:bg-indigo-800 p-0.5"
						:title="`Remove ${f.label} filter`"
						@click="removeFilter(f.key)"
					>
						<svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
							<path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
						</svg>
					</button>
				</span>
			</div>

			<div class="grid grid-cols-2 md:grid-cols-4 gap-4">
				<div
					class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700"
				>
					<div
						class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400"
					>
						Total Qualifications
					</div>
					<div class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
						{{ kpis.totalQualifications?.toLocaleString() }}
					</div>
				</div>
				<div
					class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700"
				>
					<div
						class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400"
					>
						Staff Covered
					</div>
					<div class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
						{{ kpis.staffCovered?.toLocaleString() }}
					</div>
				</div>
				<div
					class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700"
				>
					<div
						class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400"
					>
						Pending
					</div>
					<div class="mt-1 text-2xl font-bold text-yellow-600">
						{{ kpis.pending?.toLocaleString() }}
					</div>
				</div>
				<div
					class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700"
				>
					<div
						class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400"
					>
						Staff Without Quals
					</div>
					<div class="mt-1 text-2xl font-bold text-red-600">
						{{ kpis.withoutQualifications?.toLocaleString() }}
					</div>
				</div>
			</div>

			<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
				<ExpandableChart title="Qualification Level Distribution">
					<template #default="{ labelMode }">
						<LevelDistributionChart
							:distribution="levelDistribution"
							:labels="levelLabels"
							:label-mode="labelMode"
						/>
					</template>
					<template #expanded="{ labelMode }">
						<LevelDistributionChart
							:distribution="levelDistribution"
							:labels="levelLabels"
							:label-mode="labelMode"
							:expanded="true"
						/>
					</template>
				</ExpandableChart>
				<ExpandableChart title="Highest Qualification Level by Gender">
					<template #default="{ labelMode }">
						<LevelByGenderChart
							:level-by-gender="levelByGender"
							:level-labels="levelLabels"
							:label-mode="labelMode"
						/>
					</template>
					<template #expanded="{ labelMode }">
						<LevelByGenderChart
							:level-by-gender="levelByGender"
							:level-labels="levelLabels"
							:label-mode="labelMode"
							:expanded="true"
						/>
					</template>
				</ExpandableChart>
				<ExpandableChart title="Qualifications by Unit">
					<template #default="{ labelMode }">
						<ByUnitChart
							:by-unit="byUnit"
							:level-labels="levelLabels"
							:label-mode="labelMode"
						/>
					</template>
					<template #expanded="{ labelMode }">
						<ByUnitChart
							:by-unit="byUnit"
							:level-labels="levelLabels"
							:label-mode="labelMode"
							:expanded="true"
						/>
					</template>
				</ExpandableChart>
				<ExpandableChart title="Qualifications Acquired Over Time">
					<template #default="{ labelMode }">
						<AcquiredOverTimeChart
							:trend="trendByYear"
							:label-mode="labelMode"
						/>
					</template>
					<template #expanded="{ labelMode }">
						<AcquiredOverTimeChart
							:trend="trendByYear"
							:label-mode="labelMode"
							:expanded="true"
						/>
					</template>
				</ExpandableChart>
				<ExpandableChart title="Top Institutions">
					<template #default="{ labelMode }">
						<TopInstitutionsChart
							:institutions="topInstitutions"
							:label-mode="labelMode"
						/>
					</template>
					<template #expanded="{ labelMode }">
						<TopInstitutionsChart
							:institutions="topInstitutions"
							:label-mode="labelMode"
							:expanded="true"
						/>
					</template>
				</ExpandableChart>
				<ExpandableChart title="Top Qualifications">
					<template #default="{ labelMode }">
						<TopQualificationsChart
							:qualifications="topQualifications"
							:label-mode="labelMode"
						/>
					</template>
					<template #expanded="{ labelMode }">
						<TopQualificationsChart
							:qualifications="topQualifications"
							:label-mode="labelMode"
							:expanded="true"
						/>
					</template>
				</ExpandableChart>
			</div>

			<div
				class="bg-white dark:bg-gray-800 rounded-lg shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700 overflow-hidden"
			>
				<div
					class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 font-semibold text-gray-800 dark:text-gray-100"
				>
					Staff Qualifications
				</div>
				<div class="overflow-x-auto">
					<table class="min-w-full text-sm">
						<thead
							class="bg-gray-50 dark:bg-gray-900/50 text-left text-gray-700 dark:text-gray-200"
						>
							<tr>
								<th class="px-3 py-2">Name</th>
								<th class="px-3 py-2">Qualification</th>
								<th class="px-3 py-2">Level</th>
								<th class="px-3 py-2">Institution</th>
								<th class="px-3 py-2">Year</th>
								<th class="px-3 py-2">Status</th>
							</tr>
						</thead>
						<tbody
							class="divide-y divide-gray-200 dark:divide-gray-700 text-gray-700 dark:text-gray-300"
						>
							<tr
								v-for="q in staffList.data"
								:key="q.id"
								class="hover:bg-gray-50 dark:hover:bg-gray-900/50"
							>
								<td class="px-3 py-2">
									{{ q.person?.first_name }} {{ q.person?.surname }}
								</td>
								<td class="px-3 py-2">{{ q.qualification }}</td>
								<td class="px-3 py-2">{{ levelLabels[q.level] ?? q.level }}</td>
								<td class="px-3 py-2">{{ q.institution }}</td>
								<td class="px-3 py-2">{{ q.year }}</td>
								<td class="px-3 py-2">{{ q.status }}</td>
							</tr>
							<tr v-if="!staffList.data?.length">
								<td colspan="6" class="px-3 py-6 text-center text-gray-500">
									No qualifications match the current filters.
								</td>
							</tr>
						</tbody>
					</table>
				</div>
				<Pagination v-if="staffList?.total > 0" :navigation="staffList" />
			</div>
		</div>
	</MainLayout>
</template>
