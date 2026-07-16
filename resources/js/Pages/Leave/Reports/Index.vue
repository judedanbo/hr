<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, router, usePage } from "@inertiajs/vue3";
import { reactive, computed } from "vue";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import MainTable from "@/Components/MainTable.vue";
import TableHead from "@/Components/TableHead.vue";
import TableBody from "@/Components/TableBody.vue";
import RowHeader from "@/Components/RowHeader.vue";
import TableData from "@/Components/TableData.vue";
import TableRow from "@/Components/TableRow.vue";
import UtilisationChart from "@/Components/Charts/Leaves/UtilisationChart.vue";

const props = defineProps({
	year: { type: [Number, String], default: null },
	utilisationByType: { type: Array, default: () => [] },
	planVsActual: { type: Array, default: () => [] },
	staffTotals: { type: Array, default: () => [] },
	liability: { type: Number, default: 0 },
	compliance: { type: Object, default: () => ({}) },
	absencePattern: { type: Array, default: () => [] },
	kpis: { type: Object, default: () => ({}) },
	filters: { type: Object, default: () => ({}) },
	filterOptions: { type: Object, default: () => ({}) },
});

const page = usePage();
const permissions = computed(() => page.props?.auth.permissions);
const canExport = computed(() =>
	permissions.value?.includes("export leave reports"),
);

const form = reactive({
	year_id: props.filters.year_id || "",
	leave_type_id: props.filters.leave_type_id || "",
	unit_id: props.filters.unit_id || "",
});

const applyFilters = () => {
	router.get(route("leave-reports.index"), form, {
		preserveScroll: true,
		preserveState: true,
	});
};

const exportUrl = (format, type) =>
	route(`leave-reports.export.${format}`, { ...form, type });

const links = [{ name: "Leave Reports", url: "" }];
</script>

<template>
	<MainLayout>
		<Head title="Leave Reports" />
		<main class="max-w-7xl mx-auto sm:px-6 lg:px-8">
			<BreadCrumpVue :links="links" />

			<div class="mt-6 flex flex-wrap items-end justify-between gap-3">
				<h1 class="text-xl font-semibold text-gray-800 dark:text-gray-100">
					Leave Reports
					<span v-if="year" class="text-gray-400">— {{ year }}</span>
				</h1>
				<div class="flex flex-wrap items-center gap-2 text-sm">
					<select
						v-model="form.year_id"
						class="rounded-md border-gray-300 dark:bg-gray-600"
						@change="applyFilters()"
					>
						<option value="">Active year</option>
						<option
							v-for="o in filterOptions.years"
							:key="o.value"
							:value="o.value"
						>
							{{ o.label }}
						</option>
					</select>
					<select
						v-model="form.leave_type_id"
						class="rounded-md border-gray-300 dark:bg-gray-600"
						@change="applyFilters()"
					>
						<option value="">All types</option>
						<option
							v-for="o in filterOptions.leaveTypes"
							:key="o.value"
							:value="o.value"
						>
							{{ o.label }}
						</option>
					</select>
					<select
						v-model="form.unit_id"
						class="rounded-md border-gray-300 dark:bg-gray-600"
						@change="applyFilters()"
					>
						<option value="">All units</option>
						<option
							v-for="o in filterOptions.units"
							:key="o.value"
							:value="o.value"
						>
							{{ o.label }}
						</option>
					</select>
				</div>
			</div>

			<section class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
				<div class="rounded-md bg-white dark:bg-gray-800 p-4 shadow-sm">
					<p class="text-xs text-gray-400">Days taken</p>
					<p class="text-2xl font-semibold">{{ kpis.total_taken }}</p>
				</div>
				<div class="rounded-md bg-white dark:bg-gray-800 p-4 shadow-sm">
					<p class="text-xs text-gray-400">Outstanding liability (days)</p>
					<p class="text-2xl font-semibold">{{ liability }}</p>
				</div>
				<div class="rounded-md bg-white dark:bg-gray-800 p-4 shadow-sm">
					<p class="text-xs text-gray-400">Plan compliance</p>
					<p class="text-2xl font-semibold">{{ compliance.rate }}%</p>
					<p class="text-xs text-gray-400">
						{{ compliance.submitted }} / {{ compliance.total }} submitted
					</p>
				</div>
				<div class="rounded-md bg-white dark:bg-gray-800 p-4 shadow-sm">
					<p class="text-xs text-gray-400">Staff in scope</p>
					<p class="text-2xl font-semibold">{{ kpis.staff_count }}</p>
				</div>
			</section>

			<div v-if="canExport" class="mt-4 flex flex-wrap gap-2 text-sm">
				<a
					:href="exportUrl('excel', 'balances')"
					class="rounded-md bg-green-600 px-3 py-2 text-white"
					>Balances (Excel)</a
				>
				<a
					:href="exportUrl('pdf', 'balances')"
					class="rounded-md bg-gray-700 px-3 py-2 text-white"
					>Balances (PDF)</a
				>
				<a
					:href="exportUrl('excel', 'utilisation')"
					class="rounded-md bg-green-600 px-3 py-2 text-white"
					>Utilisation (Excel)</a
				>
				<a
					:href="exportUrl('excel', 'absence')"
					class="rounded-md bg-green-600 px-3 py-2 text-white"
					>Absence (Excel)</a
				>
			</div>

			<section class="mt-6 grid gap-4 lg:grid-cols-2">
				<div class="rounded-md bg-white dark:bg-gray-800 p-4 shadow-sm">
					<h2 class="font-semibold text-gray-700 dark:text-gray-100 mb-2">
						Utilisation by type
					</h2>
					<UtilisationChart :rows="utilisationByType" />
				</div>
				<div
					class="rounded-md bg-white dark:bg-gray-800 p-4 shadow-sm overflow-x-auto"
				>
					<h2 class="font-semibold text-gray-700 dark:text-gray-100 mb-2">
						Plan vs actual
					</h2>
					<MainTable>
						<TableHead>
							<RowHeader>Leave type</RowHeader>
							<RowHeader>Planned</RowHeader>
							<RowHeader>Taken</RowHeader>
						</TableHead>
						<TableBody>
							<TableRow v-for="r in planVsActual" :key="r.leave_type">
								<TableData>{{ r.leave_type }}</TableData>
								<TableData>{{ r.planned }}</TableData>
								<TableData>{{ r.taken }}</TableData>
							</TableRow>
						</TableBody>
					</MainTable>
				</div>
			</section>

			<section class="mt-6 grid gap-4 lg:grid-cols-2">
				<div
					class="rounded-md bg-white dark:bg-gray-800 p-4 shadow-sm overflow-x-auto"
				>
					<h2 class="font-semibold text-gray-700 dark:text-gray-100 mb-2">
						Absence pattern (Bradford)
					</h2>
					<MainTable>
						<TableHead>
							<RowHeader>Staff</RowHeader>
							<RowHeader>Spells</RowHeader>
							<RowHeader>Days</RowHeader>
							<RowHeader>Factor</RowHeader>
						</TableHead>
						<TableBody>
							<TableRow v-for="(r, i) in absencePattern" :key="i">
								<TableData>{{ r.staff }}</TableData>
								<TableData>{{ r.spells }}</TableData>
								<TableData>{{ r.days }}</TableData>
								<TableData>{{ r.bradford }}</TableData>
							</TableRow>
						</TableBody>
					</MainTable>
				</div>
				<div class="rounded-md bg-white dark:bg-gray-800 p-4 shadow-sm">
					<h2 class="font-semibold text-gray-700 dark:text-gray-100 mb-2">
						Plan non-submitters ({{ compliance.non_submitters?.length || 0 }})
					</h2>
					<ul
						class="text-sm text-gray-600 dark:text-gray-300 space-y-1 max-h-64 overflow-y-auto"
					>
						<li v-for="(name, i) in compliance.non_submitters" :key="i">
							{{ name }}
						</li>
						<li v-if="!compliance.non_submitters?.length" class="text-gray-400">
							Everyone has submitted.
						</li>
					</ul>
				</div>
			</section>
		</main>
	</MainLayout>
</template>
