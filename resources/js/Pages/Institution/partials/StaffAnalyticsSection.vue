<script setup>
import GenderDistributionChart from "@/Components/Charts/GenderDistributionChart.vue";
import AgeDistributionChart from "@/Components/Charts/AgeDistributionChart.vue";
import StatusBreakdownChart from "@/Components/Charts/StatusBreakdownChart.vue";

const props = defineProps({
	analytics: {
		type: Object,
		required: true,
	},
});

const emit = defineEmits(["chart-click"]);

function handleChartClick(data) {
	emit("chart-click", data);
}
</script>

<template>
	<section>
		<h2
			class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4"
		>
			Staff Analytics
		</h2>
		<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
			<GenderDistributionChart
				:male="analytics.gender?.male || 0"
				:female="analytics.gender?.female || 0"
				title="Gender Distribution"
				@segment-click="handleChartClick"
			/>
			<AgeDistributionChart
				:distribution="analytics.age_distribution || []"
				title="Age Distribution"
				@bar-click="handleChartClick"
			/>
			<StatusBreakdownChart
				:distribution="analytics.status || []"
				title="Employment Status"
				@segment-click="handleChartClick"
			/>
		</div>
	</section>
</template>
