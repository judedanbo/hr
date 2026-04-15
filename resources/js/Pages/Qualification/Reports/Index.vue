<script setup>
import { ref, computed, watch } from "vue";
import { router, Head, usePage } from "@inertiajs/vue3";
import { debouncedWatch } from "@vueuse/core";
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import Pagination from "@/Components/Pagination.vue";
import ExpandableChart from "@/Components/Charts/Qualifications/ExpandableChart.vue";
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
	return Array.isArray(perms) && perms.includes("qualifications.reports.export");
});

const breadcrumbs = [
	{ name: "Reports", url: "/reports" },
	{ name: "Qualifications", url: "" },
];

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
			<h2 class="font-semibold text-xl text-gray-800 leading-tight dark:text-gray-50">
				Qualification Reports
			</h2>
		</template>

		<div class="max-w-7xl mx-auto sm:px-6 lg:px-8 py-6 space-y-6">
			<BreadCrumpVue :links="breadcrumbs" />

			<div v-if="canExport" class="flex justify-end gap-2">
				<div class="relative group">
					<button type="button" class="px-3 py-1.5 text-sm bg-indigo-600 text-white rounded hover:bg-indigo-700">
						Export PDF &#9662;
					</button>
					<div class="absolute right-0 mt-1 hidden group-hover:block bg-white dark:bg-gray-800 shadow-lg rounded border border-gray-200 dark:border-gray-700 z-10 min-w-[180px]">
						<a v-for="rt in reportTypes" :key="'pdf-' + rt.value" :href="exportUrl('pdf', rt.value)"
							class="block px-3 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-800 dark:text-gray-100">
							{{ rt.label }}
						</a>
					</div>
				</div>
				<div class="relative group">
					<button type="button" class="px-3 py-1.5 text-sm bg-emerald-600 text-white rounded hover:bg-emerald-700">
						Export Excel &#9662;
					</button>
					<div class="absolute right-0 mt-1 hidden group-hover:block bg-white dark:bg-gray-800 shadow-lg rounded border border-gray-200 dark:border-gray-700 z-10 min-w-[180px]">
						<a v-for="rt in reportTypes" :key="'excel-' + rt.value" :href="exportUrl('excel', rt.value)"
							class="block px-3 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-800 dark:text-gray-100">
							{{ rt.label }}
						</a>
					</div>
				</div>
			</div>

			<div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700">
				<div class="grid grid-cols-1 md:grid-cols-4 gap-3">
					<select v-model="form.department_id" class="rounded border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm">
						<option value="">All Departments</option>
						<option v-for="d in filterOptions?.departments ?? []" :key="d.id" :value="d.id">{{ d.name }}</option>
					</select>
					<select v-model="form.unit_id" class="rounded border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm">
						<option value="">{{ form.department_id ? 'All Units in Department' : 'All Units' }}</option>
						<option v-for="u in availableUnits" :key="u.id" :value="u.id">{{ u.name }}</option>
					</select>
					<select v-model="form.level" class="rounded border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm">
						<option value="">All Levels</option>
						<option v-for="l in filterOptions?.levels ?? []" :key="l.value" :value="l.value">{{ l.label }}</option>
					</select>
					<select v-model="form.status" class="rounded border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm">
						<option value="">All Statuses</option>
						<option v-for="s in filterOptions?.statuses ?? []" :key="s.value" :value="s.value">{{ s.label }}</option>
					</select>
					<select v-model="form.gender" class="rounded border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm">
						<option value="">Any Gender</option>
						<option value="M">Male</option>
						<option value="F">Female</option>
					</select>
					<input v-model="form.year_from" type="number" placeholder="Year from"
						class="rounded border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm" />
					<input v-model="form.year_to" type="number" placeholder="Year to"
						class="rounded border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm" />
					<input v-model="form.institution" type="text" placeholder="Institution"
						class="rounded border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm" />
					<input v-model="form.course" type="text" placeholder="Course keyword"
						class="rounded border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm" />
				</div>
				<div class="mt-3 flex justify-end">
					<button type="button" @click="clearFilters" class="text-sm text-gray-600 dark:text-gray-300 hover:underline">
						Clear all
					</button>
				</div>
			</div>

			<div class="grid grid-cols-2 md:grid-cols-4 gap-4">
				<div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700">
					<div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Total Qualifications</div>
					<div class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ kpis.totalQualifications?.toLocaleString() }}</div>
				</div>
				<div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700">
					<div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Staff Covered</div>
					<div class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ kpis.staffCovered?.toLocaleString() }}</div>
				</div>
				<div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700">
					<div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Pending</div>
					<div class="mt-1 text-2xl font-bold text-yellow-600">{{ kpis.pending?.toLocaleString() }}</div>
				</div>
				<div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700">
					<div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Staff Without Quals</div>
					<div class="mt-1 text-2xl font-bold text-red-600">{{ kpis.withoutQualifications?.toLocaleString() }}</div>
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
						<ByUnitChart :by-unit="byUnit" :level-labels="levelLabels" :label-mode="labelMode" />
					</template>
					<template #expanded="{ labelMode }">
						<ByUnitChart :by-unit="byUnit" :level-labels="levelLabels" :label-mode="labelMode" :expanded="true" />
					</template>
				</ExpandableChart>
				<ExpandableChart title="Qualifications Acquired Over Time">
					<template #default="{ labelMode }">
						<AcquiredOverTimeChart :trend="trendByYear" :label-mode="labelMode" />
					</template>
					<template #expanded="{ labelMode }">
						<AcquiredOverTimeChart :trend="trendByYear" :label-mode="labelMode" :expanded="true" />
					</template>
				</ExpandableChart>
				<ExpandableChart title="Top Institutions">
					<template #default="{ labelMode }">
						<TopInstitutionsChart :institutions="topInstitutions" :label-mode="labelMode" />
					</template>
					<template #expanded="{ labelMode }">
						<TopInstitutionsChart :institutions="topInstitutions" :label-mode="labelMode" :expanded="true" />
					</template>
				</ExpandableChart>
				<ExpandableChart title="Top Qualifications">
					<template #default="{ labelMode }">
						<TopQualificationsChart :qualifications="topQualifications" :label-mode="labelMode" />
					</template>
					<template #expanded="{ labelMode }">
						<TopQualificationsChart :qualifications="topQualifications" :label-mode="labelMode" :expanded="true" />
					</template>
				</ExpandableChart>
			</div>

			<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700 overflow-hidden">
				<div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 font-semibold text-gray-800 dark:text-gray-100">
					Staff Qualifications
				</div>
				<div class="overflow-x-auto">
					<table class="min-w-full text-sm">
						<thead class="bg-gray-50 dark:bg-gray-900/50 text-left text-gray-700 dark:text-gray-200">
							<tr>
								<th class="px-3 py-2">Name</th>
								<th class="px-3 py-2">Qualification</th>
								<th class="px-3 py-2">Level</th>
								<th class="px-3 py-2">Institution</th>
								<th class="px-3 py-2">Year</th>
								<th class="px-3 py-2">Status</th>
							</tr>
						</thead>
						<tbody class="divide-y divide-gray-200 dark:divide-gray-700 text-gray-700 dark:text-gray-300">
							<tr v-for="q in staffList.data" :key="q.id" class="hover:bg-gray-50 dark:hover:bg-gray-900/50">
								<td class="px-3 py-2">{{ q.person?.first_name }} {{ q.person?.surname }}</td>
								<td class="px-3 py-2">{{ q.qualification }}</td>
								<td class="px-3 py-2">{{ levelLabels[q.level] ?? q.level }}</td>
								<td class="px-3 py-2">{{ q.institution }}</td>
								<td class="px-3 py-2">{{ q.year }}</td>
								<td class="px-3 py-2">{{ q.status }}</td>
							</tr>
							<tr v-if="!staffList.data?.length">
								<td colspan="6" class="px-3 py-6 text-center text-gray-500">No qualifications match the current filters.</td>
							</tr>
						</tbody>
					</table>
				</div>
				<Pagination v-if="staffList?.total > 0" :navigation="staffList" />
			</div>
		</div>
	</MainLayout>
</template>
