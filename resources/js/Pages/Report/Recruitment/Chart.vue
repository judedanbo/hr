<template>
    <div>
        <!-- <div class="flex space-x-2 justify-center">
            <button class="" type="button" @click="getAll">All</button>
            <button class="" type="button" @click="getActive">Active</button>
            <button class="" type="button" @click="getSeparated">
                Retired
            </button>
        </div> -->
        <!-- <div> -->
        <BarChart ref="barChartRef" v-bind="barChartProps" />
        <!-- </div> -->
    </div>
</template>

<script lang="ts">
import { computed, defineComponent, ref } from "vue";
import { BarChart, useBarChart } from "vue-chart-3";
import { Chart, ChartData, ChartOptions, registerables } from "chart.js";
import { Inertia } from "@inertiajs/inertia";

Chart.register(...registerables);
export default defineComponent({
    name: "App",
    components: { BarChart },
    props: {
        recruitment: Object,
        title: String,
    },
    setup(props) {
        const toggleLegend = ref(true);

        const testData = computed<ChartData<"bar">>(() => ({
            labels: props.recruitment.map((item) => {
                return item.year;
            }),
            // labels: dataLabels.value,
            datasets: [
                {
                    label: "Male",
                    data: props.recruitment.map((item) => {
                        return item.male;
                    }),
                    backgroundColor: ["#15803D"],
                },
                {
                    label: "Female",
                    data: props.recruitment.map((item) => {
                        return item.female;
                    }),
                    backgroundColor: ["#FF803D"],
                },
            ],
        }));

        const options = computed<ChartOptions<"bar">>(() => ({
            scales: {
                x: {
                    stacked: true,
                    title: {
                        display: true,
                        text: "Year of Employment",
                    },
                },
                y: {
                    stacked: true,
                    title: {
                        display: true,
                        text: "Number of Staff Employed",
                    },
                },
            },
            plugins: {
                legend: {
                    position: toggleLegend.value ? "top" : "bottom",
                },
                title: {
                    display: true,
                    text: props.title ?? "Total Recruitment",
                },
            },
        }));

        const { barChartProps, barChartRef } = useBarChart({
            chartData: testData,
            options,
        });

        function getAll() {
            Inertia.get(
                route("report.recruitment.chart"),
                {},
                { preserveState: true, replace: true }
            );
        }
        function getActive() {
            Inertia.get(
                route("report.recruitment.chart"),
                { active: true },
                { preserveState: true, replace: true }
            );
        }
        function getSeparated() {
            Inertia.get(
                route("report.recruitment.chart"),
                { retired: true },
                { preserveState: true, replace: true }
            );
        }

        function switchLegend() {
            toggleLegend.value = !toggleLegend.value;
        }

        return {
            getSeparated,
            getAll,
            getActive,
            switchLegend,
            testData,
            options,
            barChartRef,
            barChartProps,
        };
    },
});
</script>
