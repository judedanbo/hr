<script setup>
import { onMounted, reactive } from "vue";
import Chart from "chart.js/auto";
const props = defineProps({
	type: {
		type: String,
		default: "line",
	},
	title: {
		type: String,
		default: "HRMIS Chart",
	},
	labels: {
		type: Array,
		default: () => [],
	},
	datasets: {
		type: Array,
		default: () => [],
	},
});

const data = {
	labels: props.labels,
	datasets: props.datasets,
};
const options = reactive({
	responsive: true,
	plugins: {
		legend: {
			position: "top",
			labels: {
				color: "rgba(255,255,255,0.8)",
			},
		},
		title: {
			display: true,
			text: props.title,
			color: "rgba(255,255,255,0.8)",
		},
	},
	scales: {
		y: {
			ticks: {
				display: props.type == "pie" ? false : true,
				color: "rgba(255,255,255,0.8)",
			},
			border: {
				display: props.type == "pie" ? false : true,
				color: "rgba(255,255,255)",
			},
			grid: {
				display: props.type == "pie" ? false : true,
				color: "rgba(0,0,0,0.18)",
			},
		},
		x: {
			ticks: {
				display: props.type == "pie" ? false : true,
				color: "rgba(255,255,255,0.8)",
			},
			border: {
				display: props.type == "pie" ? false : true,
				color: "rgba(255,255,255)",
			},
			grid: {
				display: props.type == "pie" ? false : true,
			},
		},
	},
});
const config = reactive({
	type: props.type,
	data,
	options,
});

onMounted(() => {
	const ctx = document.getElementById("hrChart");
	const hrChart = new Chart(ctx, config);
});
</script>
<template>
	<canvas
		class="dark:bg-gray-700 bg-gray-50 w-full sm:rounded-2xl px-4 py-2"
		id="hrChart"
	></canvas>
</template>
