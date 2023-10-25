<template>
	<BarChart :ref="chartRef" :chartData="chartData" :options="options" />
</template>

<script lang="ts">
import {
	defineComponent,
	reactive,
	onMounted,
	watch,
	ref,
	computed,
} from "vue";
import { BarChart } from "vue-chart-3";
import { Chart, registerables } from "chart.js";

Chart.register(...registerables);

export default defineComponent({
	name: "Home",
	components: { BarChart },
	props: {
		recruitment: Object,
		title: String,
	},
	setup(props) {
		const chartRef = ref();
		let label = reactive([]);
		let male = reactive([]);
		let female = reactive([]);

		// let updateTable = () => {
		//     props.recruitment.forEach((element) => {
		//         label.push(element.year);
		//         male.push(element.male);
		//         female.push(element.female);
		//     });
		// };

		onMounted(() => {
			props.recruitment.forEach((element) => {
				label.push(element.year);
				male.push(element.male);
				female.push(element.female);
			});
			// chartRef.value.update();
		});

		function getMale() {
			return props.recruitment.map((item) => {
				// return item.male;
				return 5;
			});
		}

		watch(
			() => props.recruitment,
			(currentValue, oldValue) => {
				male = getMale();
				// currentValue.forEach((element) => {
				//     male.push(element.male);
				//     // female.push(element.female);
				// });
				chartData.value.datasets[0].data = male;
				chartRef.value.update();
			},
		);

		const chartData = computed(() => ({
			labels: label,
			datasets: [
				{
					label: "Male",
					data: male,
					backgroundColor: ["#15803D"],
				},
				{
					label: "Female",
					data: female,
					backgroundColor: ["#FF803D"],
				},
			],
		}));

		const options = ref({
			plugins: {
				title: {
					display: true,
					text: props.title ?? "Total Annual Recruitment",
				},
			},
			responsive: true,
			scales: {
				x: {
					stacked: true,
				},
				y: {
					stacked: true,
				},
			},
		});
		return { chartData, options, BarChart, chartRef };

		// watch(props.recruitment, () => {
		//     label = male = female = [];
		//     updateTable();
		//     chartData.datasets[0].data = male;
		//     chartData.datasets[1].data = female;
		// });
	},
});
</script>
