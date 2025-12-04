<script setup>
import { computed } from "vue";
import { Pie } from "vue-chartjs";
import { Chart as ChartJS, Title, Legend, Tooltip, ArcElement } from "chart.js";
import { useDark } from "@vueuse/core";

ChartJS.register(Title, Legend, Tooltip, ArcElement);

const isDark = useDark();

const props = defineProps({
	male: { type: Number, required: true },
	female: { type: Number, required: true },
	title: { type: String, default: "Gender Distribution" },
});

const emit = defineEmits(["segment-click"]);

const chartData = computed(() => ({
	labels: ["Male", "Female"],
	datasets: [
		{
			label: "Staff by Gender",
			borderWidth: 0,
			backgroundColor: ["#2563eb", "#fb7185"],
			data: [props.male, props.female],
		},
	],
}));

const chartOptions = computed(() => ({
	responsive: true,
	maintainAspectRatio: true,
	onClick: (event, elements) => {
		if (elements.length > 0) {
			const index = elements[0].index;
			emit("segment-click", {
				filter: "gender",
				params: { value: index === 0 ? "M" : "F" },
				title: index === 0 ? "Male Staff" : "Female Staff",
			});
		}
	},
	plugins: {
		legend: {
			position: "bottom",
			labels: {
				color: isDark.value ? "rgba(255,255,255,0.8)" : "rgba(0,0,0,0.8)",
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
				label: function (context) {
					const total = props.male + props.female;
					const percentage = ((context.raw / total) * 100).toFixed(1);
					return `${context.label}: ${context.raw?.toLocaleString()} (${percentage}%)`;
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
		<Pie :data="chartData" :options="chartOptions" class="cursor-pointer" />
	</div>
</template>
