<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, usePage } from "@inertiajs/vue3";
import { ref, computed, onMounted } from "vue";
import { router } from "@inertiajs/vue3";
import Pagination from "@/Components/Pagination.vue";
import { useToggle } from "@vueuse/core";
import Modal from "@/Components/NewModal.vue";
import NewBreadcrumb from "@/Components/NewBreadcrumb.vue";
import PageHeader from "@/Components/PageHeader.vue";
import { useNavigation } from "@/Composables/navigation";
import { useSearch } from "@/Composables/search";
import SeparatedList from "./partials/SeparatedList.vue";
import {
	ChevronLeftIcon,
	ChevronRightIcon,
	PlusIcon,
	PencilSquareIcon,
} from "@heroicons/vue/20/solid";
import PageHeading from "@/Components/PageHeading.vue";
import { Link } from "@inertiajs/vue3";
import PageActions from "@/Components/PageActions.vue";
import PageStats from "@/Components/PageStats.vue";

const page = usePage();
const permissions = computed(() => page.props.value?.auth.permissions);

const navigation = computed(() => useNavigation(props.separated));
import {
	CursorArrowRaysIcon,
	BuildingOfficeIcon,
	ArrowDownTrayIcon,
	UserGroupIcon,
} from "@heroicons/vue/24/outline";

let openAddDialog = ref(false);

let openSeparation = (staff) => {
	router.visit(route("person.show", { person: staff }));
};
let toggle = useToggle(openAddDialog);

let props = defineProps({
	separated: { type: Object, required: true },
	filters: { type: Object, default: () => {} },
});

let search = ref(props.filters.search);

const searchUnits = (value) => {
	useSearch(value, route("separation.index"));
};

const breadCrumbLinks = [
	{
		name: "Home",
		url: route("institution.show", {
			institution: 1,
		}),
	},
];
const pageActions = [
	{
		name: "Add Unit",
		color: "primary",
		icon: PlusIcon,
	},
];
// const buttonClicked = (text) => {
// 	if (text === "Add Unit") {
// 		toggle();
// 	}
// };
const stats = ref([]);
// const unitsStats = ref([]);
const totalStaff = computed(() => {
	return props.units.data
		.reduce((partialSum, currentNumber) => {
			return partialSum + currentNumber.staff;
		}, 0)
		.toLocaleString();
});
const totalUnits = computed(() => {
	return props.units.data
		.reduce((partialSum, currentNumber) => {
			return partialSum + currentNumber.units;
		}, 0)
		.toLocaleString();
});
onMounted(() => {
	// unitsStats.value = router.get(route("units.stats"));
	stats.value = [
		{
			id: 1,
			name: "Total Department",
			stat: 2424,
			icon: BuildingOfficeIcon,
			change: "2",
			changeType: "increase",
		},
		{
			id: 2,
			name: "Total Staff",
			stat: 555,
			icon: UserGroupIcon,
			change: "5.4%",
			changeType: "increase",
		},
		{
			id: 3,
			name: "Total Units",
			stat: 256,
			icon: CursorArrowRaysIcon,
			change: "3.2%",
			changeType: "decrease",
		},
	];
});
</script>

<template>
	<MainLayout>
		<Head title="Separation" />
		<main class="max-w-7xl mx-auto sm:px-6 lg:px-8">
			<!-- <BreadCrumpVue :links="BreadCrumpLinks" /> -->
			<PageHeading
				name="Separation"
				:search="search"
				@searchStaff="(search) => searchUnits(search)"
			>
				<template #breadcrumb>
					<NewBreadcrumb :links="breadCrumbLinks" />
				</template>

				<template #stats>
					<PageStats :stats="stats" />
				</template>
			</PageHeading>
			<div
				class="overflow-hidden shadow-sm sm:rounded-lg px-6 border-b border-gray-200"
			>
				<div
					v-if="permissions?.includes('download separated staff data')"
					class="flex flex-wrap gap-5 mt-4 items-center justify-center"
				>
					<a
						class="rounded-md flex gap-x-3 bg-green-600 dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600"
						:href="route('report.retirements-all')"
					>
						<arrow-down-tray-icon class="-ml-1.5 h-5 w-5" aria-hidden="true" />
						<span>All Separated</span>
					</a>
					<a
						class="rounded-md flex gap-x-3 bg-green-600 dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600"
						:href="route('report.retirements')"
					>
						<arrow-down-tray-icon class="-ml-1.5 h-5 w-5" aria-hidden="true" />
						<span>Export retired Staff</span>
					</a>
					<a
						class="rounded-md flex gap-x-3 bg-green-600 dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600"
						:href="route('report.retirements-deceased')"
					>
						<arrow-down-tray-icon class="-ml-1.5 h-5 w-5" aria-hidden="true" />
						<span>Export deceased</span>
					</a>
					<a
						class="rounded-md flex gap-x-3 bg-green-600 dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600"
						:href="route('report.retirements-terminated')"
					>
						<arrow-down-tray-icon class="-ml-1.5 h-5 w-5" aria-hidden="true" />
						<span>Terminated</span>
					</a>
					<a
						class="rounded-md flex gap-x-3 bg-green-600 dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600"
						:href="route('report.leave-pay')"
					>
						<arrow-down-tray-icon class="-ml-1.5 h-5 w-5" aria-hidden="true" />
						<span>Leave with pay</span>
					</a>
					<a
						class="rounded-md flex gap-x-3 bg-green-600 dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600"
						:href="route('report.leave-without-pay')"
					>
						<arrow-down-tray-icon class="-ml-1.5 h-5 w-5" aria-hidden="true" />
						<span>Leave without pay</span>
					</a>
					<a
						class="rounded-md flex gap-x-3 bg-green-600 dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600"
						:href="route('report.resignation')"
					>
						<arrow-down-tray-icon class="-ml-1.5 h-5 w-5" aria-hidden="true" />
						<span>Resignation</span>
					</a>
					<a
						class="rounded-md flex gap-x-3 bg-green-600 dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600"
						:href="route('report.suspended')"
					>
						<arrow-down-tray-icon class="-ml-1.5 h-5 w-5" aria-hidden="true" />
						<span>Suspended</span>
					</a>
					<a
						class="rounded-md flex gap-x-3 bg-green-600 dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600"
						:href="route('report.vol-retirement')"
					>
						<arrow-down-tray-icon class="-ml-1.5 h-5 w-5" aria-hidden="true" />
						<span>Voluntary retirement</span>
					</a>
					<a
						class="rounded-md flex gap-x-3 bg-green-600 dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600"
						:href="route('report.dismissed')"
					>
						<arrow-down-tray-icon class="-ml-1.5 h-5 w-5" aria-hidden="true" />
						<span>Dismissed</span>
					</a>
					<a
						class="rounded-md flex gap-x-3 bg-green-600 dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600"
						:href="route('report.vacation-of-post')"
					>
						<arrow-down-tray-icon class="-ml-1.5 h-5 w-5" aria-hidden="true" />
						<span>Vacation of post</span>
					</a>
				</div>
				<!-- <PageHeader
					title="Departments"
					:total="units.total"
					:search="search"
					action-text="Add Unit"
					@action-clicked="toggle()"
					@search-entered="(value) => searchUnits(value)"
				/> -->

				<SeparatedList
					:separated="separated.data"
					@open-separation="(unitId) => openSeparation(unitId)"
				>
					<template #pagination>
						<Pagination :navigation="navigation" />
					</template>
				</SeparatedList>
			</div>
		</main>
		<Modal :show="openAddDialog" @close="toggle()">
			<!-- <AddUnit :units="parentUnits" :institution="units.data[0].institution" /> -->
		</Modal>
	</MainLayout>
</template>
