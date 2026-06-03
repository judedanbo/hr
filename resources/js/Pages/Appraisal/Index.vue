<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, router } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import Pagination from "@/Components/Pagination.vue";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import MainTable from "@/Components/MainTable.vue";
import TableHead from "@/Components/TableHead.vue";
import TableBody from "@/Components/TableBody.vue";
import RowHeader from "@/Components/RowHeader.vue";
import TableData from "@/Components/TableData.vue";
import TableRow from "@/Components/TableRow.vue";
import { useNavigation } from "@/Composables/navigation";

const props = defineProps({
	appraisals: { type: Object, required: true },
	cycles: { type: Array, default: () => [] },
	filters: { type: Object, default: () => ({}) },
});

const navigation = computed(() => useNavigation(props.appraisals));
const cycleFilter = ref(props.filters.cycle_id ?? "");

const applyFilter = () => {
	router.get(route("appraisal.index"), { cycle_id: cycleFilter.value || undefined }, { preserveState: true, replace: true });
};

const open = (id) => router.visit(route("appraisal.show", { appraisal: id }));

const tableCols = ["Staff", "Cycle", "Unit", "Appraiser", "Status", "Overall"];
const links = [{ name: "Appraisals", url: "" }];
</script>

<template>
	<MainLayout>
		<Head title="Appraisals" />
		<main class="max-w-7xl mx-auto sm:px-6 lg:px-8">
			<BreadCrumpVue :links="links" />
			<div class="overflow-hidden shadow-sm sm:rounded-lg px-6 border-b border-gray-200">
				<div class="flex items-center justify-between my-4">
					<h1 class="text-xl font-semibold dark:text-gray-100">Appraisals</h1>
					<div class="flex items-center gap-3">
						<a :href="route('appraisal.report.index')" class="rounded-md bg-indigo-600 px-3 py-2 text-sm text-white hover:bg-indigo-500">Reports</a>
						<select v-model="cycleFilter" class="rounded-md border-gray-300 dark:bg-gray-700 dark:text-gray-100" @change="applyFilter">
							<option value="">All cycles</option>
							<option v-for="cycle in cycles" :key="cycle.value" :value="cycle.value">{{ cycle.label }}</option>
						</select>
					</div>
				</div>
				<section class="flex flex-col -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
					<div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
						<div class="overflow-hidden border-b border-gray-200 rounded-md shadow-md">
							<MainTable>
								<TableHead>
									<RowHeader v-for="(column, id) in tableCols" :key="id">{{ column }}</RowHeader>
								</TableHead>
								<TableBody>
									<TableRow v-for="appraisal in appraisals.data" :key="appraisal.id" class="cursor-pointer" @click="open(appraisal.id)">
										<TableData>{{ appraisal.staff_name }}</TableData>
										<TableData>{{ appraisal.cycle }}</TableData>
										<TableData>{{ appraisal.unit ?? "—" }}</TableData>
										<TableData>{{ appraisal.appraiser_name }}</TableData>
										<TableData><span :class="appraisal.status_color">{{ appraisal.status_label }}</span></TableData>
										<TableData>{{ appraisal.overall_score ?? "—" }} <span v-if="appraisal.overall_band" class="text-xs text-gray-500">({{ appraisal.overall_band }})</span></TableData>
									</TableRow>
								</TableBody>
							</MainTable>
							<Pagination :navigation="navigation" />
						</div>
					</div>
				</section>
			</div>
		</main>
	</MainLayout>
</template>
