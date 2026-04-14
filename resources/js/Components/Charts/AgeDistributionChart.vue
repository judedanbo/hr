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
	distribution: {
		type: Array,
		required: true,
	},
	title: { type: String, default: "Age Distribution" },
});

const emit = defineEmits(["bar-click"]);

const ageRangeToMinMax = {
	"18-29": { min: 18, max: 29 },
	"30-39": { min: 30, max: 39 },
	"40-49": { min: 40, max: 49 },
	"50-59": { min: 50, max: 59 },
	"60+": { min: 60, max: 100 },
};

const chartData = computed(() => ({
	labels: props.distribution.map((d) => d.range),
	datasets: [
		{
			label: "Staff Count",
			backgroundColor: [
				"#10b981",
				"#3b82f6",
				"#8b5cf6",
				"#f59e0b",
				"#ef4444",
			],
			borderRadius: 4,
			data: props.distribution.map((d) => d.count),
		},
	],
}));

const chartOptions = computed(() => ({
	responsive: true,
	maintainAspectRatio: true,
	onClick: (event, elements) => {
		if (elements.length > 0) {
			const index = elements[0].index;
			const range = props.distribution[index].range;
			const params = ageRangeToMinMax[range];
			if (params) {
				emit("bar-click", {
					filter: "age",
					params,
					title: `Staff Age ${range}`,
				});
			}
		}
	},
	scales: {
		x: {
			ticks: {
				color: isDark.value ? "rgba(255,255,255,0.7)" : "rgba(0,0,0,0.7)",
			},
			grid: {
				display: false,
			},
		},
		y: {
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
			display: false,
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
	},
}));
</script>

<template>
	<div
		class="bg-white dark:bg-gray-800 rounded-lg shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700 p-4"
	>
		<Bar
			:data="chartData"
			:options="chartOptions"
			class="cursor-pointer"
		/>
	</div>
</template>
