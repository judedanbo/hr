<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, Link, usePage } from "@inertiajs/inertia-vue3";
import { onMounted, ref, watch, computed } from "vue";
import { Inertia } from "@inertiajs/inertia";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import RecruitmentChart from "./Chart.vue";
import Pagination from "@/Components/Pagination.vue";
import StaffTableRow from "./StaffTableRow.vue";
import SelectMenu from "@/Components/SelectMenu.vue";
import debounce from "lodash/debounce";
import { ArrowDownTrayIcon } from "@heroicons/vue/24/outline";
import { useNavigation } from "@/Composables/navigation";

let props = defineProps({
	filters: Object,
	staff: Object,
	jobs: Array,
	active: Array,
	retired: Array,
});
const navigation = computed(() => useNavigation(props.staff));
const selectedRanks = ref([]);

watch(
	selectedRanks,
	debounce(function (value) {
		let data = ref({
			ranks: selectedRanks.value.map((item) => item).join("|"),
		});

		Inertia.get(route("report.recruitment.details"), data, {
			preserveState: true,
			replace: true,
			preserveScroll: true,
		});
	}, 300),
);

let selectedYears = ref(new Set());

let getRetired = () => {
	Inertia.get(
		route("report.recruitment.details"),
		{ retired: true },
		{ preserveState: true, replace: true, preserveScroll: true },
	);
};

let getActive = () => {
	Inertia.get(
		route("report.recruitment.details"),
		{ active: true },
		{ preserveState: true, replace: true, preserveScroll: true },
	);
};
let getAll = () => {
	Inertia.get(
		route("report.recruitment.details"),
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
		route("report.recruitment.details", { year: year }),
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
		name: "Recruitment",
		url: route("report.recruitment"),
	},
	{
		name: "Details",
		url: "",
	},
];
</script>

<template>
	<Head title="Recruitment Details" />

	<MainLayout>
		<template #header>
			<h2
				class="font-semibold text-xl text-gray-800 leading-tight dark:text-gray-50"
			>
				Recruitment Details
			</h2>
		</template>
		<div class="max-w-7xl mx-auto">
			<BreadCrumpVue :links="BreadCrumpLinks" />
			<div class="overflow-hidden shadow-sm sm:rounded-lg">
				<div class="border-b border-gray-200">
					<div class="flex flex-col">
						<div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
							<div class="inline-block min-w-full align-middle sm:px-6 lg:px-8">
								<div class="flex gap-4 flex-wrap justify-center">
									<div class="bg-white py-2 px-4 md:px-8 xl:px-10 w-full pt-4">
										<div
											class="sm:flex items-center justify-between justify-items-start"
										>
											<div class="flex items-center justify-center">
												<div
													@click="getAll"
													class="rounded-full focus:outline-none focus:ring-2 focus:bg-green-50 focus:ring-green-800 cursor-pointer"
												>
													<div
														:class="{
															' bg-green-100 text-green-700 font-bold':
																route().current('report.recruitment.details', {
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
																route().current('report.recruitment.details', {
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
															route().current('report.recruitment.details', {
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
											<div class="flex">
												<a
													:href="route('report.recruitment.export-data')"
													class="flex items-center justify-center gap-2 cursor-pointer px-4 border-b border-green-500 hover:text-white hover:bg-green-800 hover:border-white"
												>
													<ArrowDownTrayIcon class="w-4 h-4 hover:text-white" />
													Download
												</a>
												<SelectMenu
													v-model="selectedRanks"
													:options="jobs"
													placeholder="Select Rank"
													multiple
												/>
											</div>
										</div>
										<div class="mt-2 overflow-x-auto">
											<table class="w-full whitespace-nowrap">
												<thead>
													<tr class="bg-gray-100">
														<th
															class="tracking-wider text-gray-500 text-sm py-2"
														>
															Name
														</th>
														<th class="tracking-wider text-gray-500 text-sm">
															Staff Number
														</th>
														<th class="tracking-wider text-gray-500 text-sm">
															Date Employed
														</th>

														<th class="tracking-wider text-gray-500 text-sm">
															Current Unit
														</th>
														<th class="tracking-wider text-gray-500 text-sm">
															Current Rank
														</th>
													</tr>
												</thead>
												<tbody>
													<StaffTableRow
														:staff="staff"
														v-for="staff in staff.data"
														:id="staff.id"
													/>
												</tbody>
											</table>
											<Pagination :navigation="navigation" />
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
