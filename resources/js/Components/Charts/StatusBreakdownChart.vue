<script setup>
import { computed } from "vue";
import { Doughnut } from "vue-chartjs";
import { Chart as ChartJS, Title, Legend, Tooltip, ArcElement } from "chart.js";
import { useDark } from "@vueuse/core";

ChartJS.register(Title, Legend, Tooltip, ArcElement);

const isDark = useDark();

const props = defineProps({
	distribution: {
		type: Array,
		required: true,
	},
	title: { type: String, default: "Employment Status" },
});

const emit = defineEmits(["segment-click"]);

const statusColors = {
	Active: "#10b981",
	Retired: "#6b7280",
	Leave: "#f59e0b",
	Suspended: "#ef4444",
	Separated: "#8b5cf6",
};

const chartData = computed(() => ({
	labels: props.distribution.map((d) => d.status),
	datasets: [
		{
			label: "Staff by Status",
			borderWidth: 0,
			backgroundColor: props.distribution.map(
				(d) => statusColors[d.status] || "#9ca3af",
			),
			data: props.distribution.map((d) => d.count),
		},
	],
}));

const chartOptions = computed(() => ({
	responsive: true,
	maintainAspectRatio: true,
	cutout: "50%",
	onClick: (event, elements) => {
		if (elements.length > 0) {
			const index = elements[0].index;
			const status = props.distribution[index];
			emit("segment-click", {
				filter: "status",
				params: { code: status.code },
				title: `${status.status} Staff`,
			});
		}
	},
	plugins: {
		legend: {
			position: "right",
			labels: {
				color: isDark.value ? "rgba(255,255,255,0.8)" : "rgba(0,0,0,0.8)",
				padding: 12,
				usePointStyle: true,
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
					const total = props.distribution.reduce((sum, d) => sum + d.count, 0);
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
		<Doughnut
			:data="chartData"
			:options="chartOptions"
			class="cursor-pointer"
		/>
	</div>
</template>
