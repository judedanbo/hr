<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, Link } from "@inertiajs/vue3";
import BreezeInput from "@/Components/Input.vue";
import { ref, watch } from "vue";
import { debouncedWatch } from "@vueuse/core";
import { router } from "@inertiajs/vue3";
import Pagination from "../../Components/Pagination.vue";
import format from "date-fns/format";
import differenceInYears from "date-fns/differenceInYears";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import ReportCard from "@/Components/ReportCard.vue";
import RecruitmentChart from "./Recruitment/Chart.vue";
import BaseChart from "@/Components/Charts/BaseChart.vue";

let BreadCrumpLinks = [
	{
		name: "Reports",
		url: "",
	},
];
const datasets = [
	{
		label: "My First Dataset",
		data: [65, 59, 80, 81, 56, 55, 40],
		fill: true,
		borderColor: "rgb(75, 192, 192)",
		tension: 0.1,
	},
];
const labels = ["January", "February", "March", "April", "May", "June", "July"];
</script>

<template>
	<Head title="Reports" />

	<MainLayout>
		<template #header>
			<h2
				class="font-semibold text-xl text-gray-800 leading-tight dark:text-gray-50"
			>
				Reports
			</h2>
		</template>
		<div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
			<BreadCrumpVue :links="BreadCrumpLinks" />
			<div class="overflow-hidden shadow-sm sm:rounded-lg">
				<div class="px-6 border-b border-gray-200">
					<div class="flex flex-col mt-6">
						<div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
							<div
								class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8"
							>
								<div class="flex gap-4 flex-wrap justify-center">
									<ReportCard
										:href="route('report.recruitment')"
										text="By Recruitment"
									/>
									<ReportCard href="#" text="By Department" />
									<ReportCard href="#" text="By Unit" />
									<ReportCard href="#" text="By Ranks" />
									<ReportCard href="#" text="By Location" />
								</div>
								<!-- <RecruitmentChart
                                    :data="[50, 52]"
                                    :label="['jan', 'dec']"
                                /> -->
								<BaseChart
									class="h-1/2"
									type="bar"
									title="HRMIS Chart"
									:labels="labels"
									:datasets="datasets"
								/>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</MainLayout>
</template>
