<script setup>
import { computed } from "vue";
import { Bar } from "vue-chartjs";
import {
	Chart as ChartJS,
	Title,
	Legend,
	Tooltip,
	BarElement,
	CategoryScale,
	LinearScale,
} from "chart.js";
import { useDark } from "@vueuse/core";

ChartJS.register(Title, Legend, Tooltip, BarElement, CategoryScale, LinearScale);

const isDark = useDark();

const props = defineProps({
	recruitment: {
		type: Array,
		required: true,
	},
	title: { type: String, default: "Recruitment Trends" },
});

const chartData = computed(() => ({
	labels: props.recruitment.map((d) => d.year),
	datasets: [
		{
			label: "Male",
			backgroundColor: "#2563eb",
			borderRadius: 4,
			data: props.recruitment.map((d) => d.male),
			stack: "Stack 0",
		},
		{
			label: "Female",
			backgroundColor: "#fb7185",
			borderRadius: 4,
			data: props.recruitment.map((d) => d.female),
			stack: "Stack 0",
		},
	],
}));

const chartOptions = computed(() => ({
	responsive: true,
	maintainAspectRatio: false,
	interaction: {
		mode: "index",
		intersect: false,
	},
	scales: {
		x: {
			stacked: true,
			ticks: {
				color: isDark.value ? "rgba(255,255,255,0.7)" : "rgba(0,0,0,0.7)",
			},
			grid: {
				display: false,
			},
		},
		y: {
			stacked: true,
			beginAtZero: true,
			ticks: {
				color: isDark.value ? "rgba(255,255,255,0.7)" : "rgba(0,0,0,0.7)",
			},
			grid: {
				color: isDark.value ? "rgba(255,255,255,0.1)" : "rgba(0,0,0,0.1)",
			},
		},
	},
	plugins: {
		legend: {
			position: "top",
			labels: {
				color: isDark.value ? "rgba(255,255,255,0.8)" : "rgba(0,0,0,0.8)",
				usePointStyle: true,
				padding: 16,
			},
		},
		title: {
			display: true,
			text: props.title,
			color: isDark.value ? "rgba(255,255,255,0.8)" : "rgba(0,0,0,0.7)",
			font: {
				size: 14,
				weight: "bold",
			},
		},
		tooltip: {
			callbacks: {
				footer: function (tooltipItems) {
					let total = 0;
					tooltipItems.forEach((item) => {
						total += item.parsed.y;
					});
					return "Total: " + total;
				},
			},
		},
	},
}));
</script>

<template>
	<div
		class="bg-white dark:bg-gray-800 rounded-lg shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700 p-4"
	>
		<div class="h-64">
			<Bar :data="chartData" :options="chartOptions" />
		</div>
	</div>
</template>
