<script setup>
import { ref, onMounted, computed } from "vue";
import axios from "axios";
import { Link, usePage } from "@inertiajs/vue3";
import LevelDistributionChart from "@/Components/Charts/Qualifications/LevelDistributionChart.vue";
import ByUnitChart from "@/Components/Charts/Qualifications/ByUnitChart.vue";
import TopInstitutionsChart from "@/Components/Charts/Qualifications/TopInstitutionsChart.vue";
import AcquiredOverTimeChart from "@/Components/Charts/Qualifications/AcquiredOverTimeChart.vue";
import PendingApprovalsWidget from "@/Components/Charts/Qualifications/PendingApprovalsWidget.vue";

const page = usePage();
const loading = ref(true);
const data = ref(null);

const levelLabels = {
	sssce_wassce: "SSSCE/WASSCE",
	certificate: "Certificate",
	diploma: "Diploma",
	hnd: "HND",
	degree: "Degree",
	pg_certificate: "PG Certificate",
	pg_diploma: "PG Diploma",
	masters: "Masters",
	doctorate: "Doctorate/PHD",
	professional: "Professional",
};

const canView = computed(() => {
	const perms = page.props.auth?.permissions ?? [];
	return Array.isArray(perms) && perms.includes("qualifications.reports.view");
});

onMounted(async () => {
	if (!canView.value) {
		loading.value = false;
		return;
	}
	try {
		const res = await axios.get("/dashboard/qualifications-widgets");
		data.value = res.data;
	} catch (e) {
		// Silent fail; section simply won't render charts.
	} finally {
		loading.value = false;
	}
});
</script>

<template>
	<section v-if="canView" class="space-y-4">
		<div class="flex items-center justify-between">
			<h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
				Workforce Qualifications
			</h2>
			<Link
				:href="route('qualifications.reports.index')"
				class="text-sm text-indigo-600 hover:underline"
			>
				Full Reports &rarr;
			</Link>
		</div>

		<div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 gap-4">
			<div v-for="n in 4" :key="n"
				class="h-72 bg-gray-100 dark:bg-gray-800 rounded animate-pulse"></div>
		</div>

		<div v-else-if="data" class="grid grid-cols-1 md:grid-cols-2 gap-4">
			<LevelDistributionChart :distribution="data.levelDistribution" :labels="levelLabels" />
			<ByUnitChart :by-unit="data.byUnit" :level-labels="levelLabels" />
			<TopInstitutionsChart :institutions="data.topInstitutions" />
			<AcquiredOverTimeChart :trend="data.trendByYear" />
			<PendingApprovalsWidget
				:count="data.pendingApprovals.count"
				:sparkline="data.pendingApprovals.sparkline"
			/>
			<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700 p-4 flex flex-col">
				<h3 class="text-sm font-medium text-gray-600 dark:text-gray-300">
					Staff Without Qualifications
				</h3>
				<div class="mt-2 text-3xl font-bold text-red-600">
					{{ data.staffWithoutQualificationsCount.toLocaleString() }}
				</div>
				<Link
					:href="route('qualifications.reports.index')"
					class="mt-auto text-xs text-indigo-600 hover:underline"
				>
					View list &rarr;
				</Link>
			</div>
		</div>
	</section>
</template>
