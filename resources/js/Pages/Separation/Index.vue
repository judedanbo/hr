<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head } from "@inertiajs/inertia-vue3";
import { ref, computed, onMounted } from "vue";
import { Inertia } from "@inertiajs/inertia";
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
import { Link } from "@inertiajs/inertia-vue3";
import PageActions from "@/Components/PageActions.vue";
import PageStats from "@/Components/PageStats.vue";

const navigation = computed(() => useNavigation(props.separated));
import {
	CursorArrowRaysIcon,
	BuildingOfficeIcon,
	UserGroupIcon,
} from "@heroicons/vue/24/outline";

let openAddDialog = ref(false);

let openSeparation = (staff) => {
	Inertia.visit(route("person.show", { person: staff }));
};
let toggle = useToggle(openAddDialog);

let props = defineProps({
	separated: { type: Object, required: true },
	filters: { type: Object, default: () => {} },
});

let search = ref(props.filters.search);

const searchUnits = (value) => {
	console.log("seearch");
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
	// unitsStats.value = Inertia.get(route("units.stats"));
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
				@searchStaff="(search) => searchUnits(search)"
				:search="search"
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
