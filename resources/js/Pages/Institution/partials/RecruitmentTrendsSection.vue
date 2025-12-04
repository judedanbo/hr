<script setup>
import RecruitmentTrendsChart from "@/Components/Charts/RecruitmentTrendsChart.vue";

const props = defineProps({
	trends: {
		type: Object,
		required: true,
	},
});
</script>

<template>
	<section>
		<h2
			class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4"
		>
			Recruitment Trends
		</h2>
		<RecruitmentTrendsChart
			:recruitment="trends.recruitment || []"
			title="Staff Recruitment by Year"
		/>
	</section>
</template>
