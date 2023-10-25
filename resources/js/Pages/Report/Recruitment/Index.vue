<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, Link } from "@inertiajs/inertia-vue3";
import { onMounted, ref, watch } from "vue";
import { Inertia } from "@inertiajs/inertia";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import RecruitmentChart from "./Chart.vue";

let props = defineProps({
	recruitment: Object,
});

let selectedYears = ref(new Set());

let download = () => {
	const imageLink = document.createElement("a");
	const canvas = document.getElementById("bar-chart");

	imageLink.href = canvas.toDataURL("image/png", 1);
	imageLink.download = "last 10 recruitment.png";
	// document.write('<img src="' + imageLink + '"/>');
	imageLink.click();
};

let getRetired = () => {
	Inertia.get(
		route("report.recruitment"),
		{ retired: true },
		{ preserveState: true, replace: true, preserveScroll: true },
	);
};

let getActive = () => {
	Inertia.get(
		route("report.recruitment"),
		{ active: true },
		{ preserveState: true, replace: true, preserveScroll: true },
	);
};
let getAll = () => {
	Inertia.get(
		route("report.recruitment"),
		{},
		{
			preserveState: true,
			replace: true,
			preserveScroll: true,
		},
	);
};

let showDetails = (year) => {
	Inertia.get(
		route("report.recruitment.details"),
		{},
		{ preserveState: true, replace: true },
	);
};

let addYear = (year) => {
	if (selectedYears.value.has(year)) {
		selectedYears.value.delete(year);
	} else {
		selectedYears.value.add(year);
	}
};
let addAllYears = () => {
	if (selectedYears.value.size > recruitment) {
		selectedYears.value.delete(year);
	} else {
		selectedYears.value.add(year);
	}
};

let BreadCrumpLinks = [
	{
		name: "Reports",
		url: route("report.index"),
	},
	{
		name: "by recruitment",
		url: "",
	},
];
</script>

<template>
	<Head title="Recruitment" />

	<MainLayout>
		<template #header>
			<h2
				class="font-semibold text-xl text-gray-800 leading-tight dark:text-gray-50"
			>
				Recruitment
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
									<!-- component -->
									<div class="sm:px-6 w-full">
										<!--- more free and premium Tailwind CSS components at https://tailwinduikit.com/ --->
										<div class="px-4 md:px-10">
											<div class="flex items-center justify-between">
												<p
													tabindex="0"
													class="focus:outline-none text-base sm:text-lg md:text-xl lg:text-2xl font-bold leading-normal text-gray-800"
												>
													Year and Number Recruited
												</p>
												<div
													class="py-3 px-4 flex items-center text-sm font-medium leading-none text-gray-600 bg-gray-200 hover:bg-gray-300 cursor-pointer rounded"
												>
													<p>Sort By:</p>
													<select
														aria-label="select"
														class="focus:text-green-600 focus:outline-none bg-transparent ml-1"
													>
														<option class="text-sm text-green-800">
															Latest
														</option>
														<option class="text-sm text-green-800">
															Oldest
														</option>
													</select>
												</div>
											</div>
										</div>
										<div class="flex gap-4">
											<div
												class="bg-white py-4 md:py-7 px-4 md:px-8 xl:px-10 w-full lg:w-1/2"
											>
												<div
													class="sm:flex items-center justify-between justify-items-start"
												>
													<div class="flex items-center">
														<div
															@click="getAll"
															class="rounded-full focus:outline-none focus:ring-2 focus:bg-green-50 focus:ring-green-800 cursor-pointer"
														>
															<div
																:class="{
																	' bg-green-100 text-green-700 font-bold':
																		route().current('report.recruitment', {
																			retired: null,
																			active: null,
																		}),
																}"
																class="py-2 px-8 rounded-full"
															>
																<p>All</p>
															</div>
														</div>
														<div
															@click="getActive"
															class="rounded-full focus:outline-none focus:ring-2 focus:bg-green-50 focus:ring-green-800 ml-4 sm:ml-8 cursor-pointer"
														>
															<div
																:class="{
																	'bg-green-100 text-green-700 font-bold':
																		route().current('report.recruitment', {
																			active: 'true',
																		}),
																}"
																class="py-2 px-8 text-gray-600 hover:text-green-700 hover:bg-green-100 rounded-full"
															>
																<p>Active</p>
															</div>
														</div>
														<div
															@click="getRetired"
															:class="{
																'bg-green-100 text-green-700 font-bold':
																	route().current('report.recruitment', {
																		retired: 'true',
																	}),
															}"
															class="rounded-full focus:outline-none focus:ring-2 focus:bg-green-50 focus:ring-green-800 ml-4 sm:ml-8 cursor-pointer"
														>
															<div
																class="py-2 px-8 text-gray-600 hover:text-green-700 hover:bg-green-100 rounded-full"
															>
																<p>Retired</p>
															</div>
														</div>
													</div>
												</div>
												<div class="mt-7 overflow-x-auto">
													<table class="w-full whitespace-nowrap">
														<thead>
															<tr>
																<th>Year</th>
																<th class="text-right">Male</th>
																<th class="text-right">Female</th>
																<th class="text-right">Total</th>
																<th class="text-right"></th>
															</tr>
														</thead>
														<tbody>
															<tr
																v-for="data in recruitment"
																:key="data.year"
																tabindex="0"
																class="focus:outline-none h-10 border border-gray-100 rounded"
															>
																<td class="">
																	<div class="flex items-center pl-5">
																		<p
																			class="text-base font-medium leading-none text-gray-700 mr-2"
																		>
																			{{ data.year }}
																		</p>
																	</div>
																</td>

																<td class="pl-5">
																	<div class="flex items-center justify-end">
																		<p
																			class="text-sm leading-none text-gray-600 ml-2"
																		>
																			{{ data.male ?? 0 }}
																		</p>
																	</div>
																</td>
																<td class="pl-5">
																	<div class="flex items-center justify-end">
																		<p
																			class="text-sm leading-none text-gray-600 ml-2"
																		>
																			{{ data.female ?? 0 }}
																		</p>
																	</div>
																</td>
																<td class="pl-5">
																	<div class="flex items-center justify-end">
																		<p
																			class="text-sm leading-none text-gray-600 ml-2"
																		>
																			{{ data.total }}
																		</p>
																	</div>
																</td>
															</tr>
														</tbody>
													</table>
												</div>
												<div class="flex space-x-3 justify-center mt-4">
													<Link
														:href="route('report.recruitment.details')"
														class="cursor-pointer px-4 py-1 rounded-full border-2 border-green-500 hover:text-white hover:bg-green-800 hover:border-white"
													>
														Details
													</Link>
													<a
														:href="route('report.recruitment.export-summary')"
														class="cursor-pointer px-4 py-1 rounded-full border-2 border-green-500 hover:text-white hover:bg-green-800 hover:border-white"
													>
														Download Data
													</a>
												</div>
											</div>

											<div class="bg-white w-1/2 rounded px-4 py-8">
												<RecruitmentChart
													:recruitment="recruitment"
													title="Last Ten Recruitment"
												/>
												<div class="flex space-x-3 justify-center mt-4">
													<Link
														:href="route('report.recruitment.chart')"
														class="cursor-pointer px-4 py-1 rounded-full border-2 border-green-500 hover:text-white hover:bg-green-800 hover:border-white"
													>
														Full Chart
													</Link>
													<div
														@click="download"
														class="cursor-pointer px-4 py-1 rounded-full border-2 border-green-500 hover:text-white hover:bg-green-800 hover:border-white"
													>
														Download Chart
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</MainLayout>
</template>
