<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head } from "@inertiajs/vue3";
import { ref, computed, onMounted } from "vue";
import { router } from "@inertiajs/vue3";
import Pagination from "../../Components/Pagination.vue";
import { useToggle } from "@vueuse/core";
import Modal from "@/Components/NewModal.vue";
import NewBreadcrumb from "@/Components/NewBreadcrumb.vue";
import AddUnit from "./partials/Add.vue";
import PageHeader from "@/Components/PageHeader.vue";
import UnitsList from "./partials/UnitsList.vue";
import { useNavigation } from "@/Composables/navigation";
import { useSearch } from "@/Composables/search";
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
const navigation = computed(() => useNavigation(props.units));
import {
	CursorArrowRaysIcon,
	BuildingOfficeIcon,
	UserGroupIcon,
} from "@heroicons/vue/24/outline";

let openAddDialog = ref(false);

let toggle = useToggle(openAddDialog);

let props = defineProps({
	units: { type: Object, required: true },
	filters: { type: Object, default: () => {} },
});

let search = ref(props.filters.search);

let parentUnits = ref([
	{
		value: null,
		label: "Select Parent Unit",
	},
]);
props.units.data.map((unit) => {
	parentUnits.value.push({
		value: unit.id,
		label: unit.name,
	});
});

const searchUnits = (value) => {
	useSearch(value, route("unit.index"));
};

let openUnit = (unit) => {
	router.visit(route("unit.show", { unit: unit }));
};

const breadCrumbLinks = [
	{
		name: props.units.data[0]?.institution.name,
		url:
			props.units.data[0]?.len() > 1
				? route("institution.show", {
						institution: props.units.data[0]?.institution.id,
				  })
				: "/institution",
	},
];
const pageActions = [
	{
		name: "Add Unit",
		color: "primary",
		icon: PlusIcon,
	},
];
const buttonClicked = (text) => {
	if (text === "Add Unit") {
		toggle();
	}
};
const stats = ref([]);
const unitsStats = ref([]);
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
			stat: props.units.total.toLocaleString(),
			icon: BuildingOfficeIcon,
			change: "2",
			changeType: "increase",
		},
		{
			id: 2,
			name: "Total Staff",
			stat: totalStaff,
			icon: UserGroupIcon,
			change: "5.4%",
			changeType: "increase",
		},
		{
			id: 3,
			name: "Total Units",
			stat: totalUnits,
			icon: CursorArrowRaysIcon,
			change: "3.2%",
			changeType: "decrease",
		},
	];
});
</script>

<template>
	<MainLayout>
		<Head title="Departments" />
		<main class="max-w-7xl mx-auto sm:px-6 lg:px-8">
			<!-- <BreadCrumpVue :links="BreadCrumpLinks" /> -->
			<PageHeading
				name="Departments"
				:search="search"
				@search="(search) => searchUnits(search)"
			>
				<template #breadcrumb>
					<NewBreadcrumb :links="breadCrumbLinks" />
				</template>
				<template #actions>
					<PageActions
						:actions="pageActions"
						@button-clicked="(text) => buttonClicked(text)"
					/>
				</template>

				<template #stats>
					<PageStats :stats="stats" />
				</template>
			</PageHeading>
			<div
				class="overflow-hidden shadow-sm sm:rounded-lg px-6 border-b border-gray-200"
			>
				<!-- <PageHeader
					title="Departments"
					:total="units.total"
					:search="search"
					action-text="Add Unit"
					@action-clicked="toggle()"
					@search-entered="(value) => searchUnits(value)"
				/> -->

				<UnitsList
					:units="units.data"
					@open-unit="(unitId) => openUnit(unitId)"
				>
					<template #pagination>
						<Pagination :navigation="navigation" />
					</template>
				</UnitsList>
			</div>
		</main>
		<Modal :show="openAddDialog" @close="toggle()">
			<AddUnit :units="parentUnits" :institution="units.data[0].institution" />
		</Modal>
	</MainLayout>
</template>
