<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head } from "@inertiajs/inertia-vue3";
import { ref, computed } from "vue";
import { Inertia } from "@inertiajs/inertia";
import Pagination from "../../Components/Pagination.vue";
import { useToggle } from "@vueuse/core";
import Modal from "@/Components/NewModal.vue";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import AddUnit from "./partials/Add.vue";
import PageHeader from "@/Components/PageHeader.vue";
import UnitsList from "./partials/UnitsList.vue";
import { useNavigation } from "@/Composables/navigation";
import { useSearch } from "@/Composables/search";

const navigation = computed(() => useNavigation(props.units));

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
	Inertia.visit(route("unit.show", { unit: unit }));
};

let BreadCrumpLinks = [
	{
		name: props.units.data[0].institution.name,
		url: route("institution.show", {
			institution: props.units.data[0].institution.id,
		}),
	},
	{
		name: "Departments",
	},
];
</script>

<template>
	<MainLayout>
		<Head title="Departments" />
		<main class="max-w-7xl mx-auto sm:px-6 lg:px-8">
			<BreadCrumpVue :links="BreadCrumpLinks" />
			<div
				class="overflow-hidden shadow-sm sm:rounded-lg px-6 border-b border-gray-200"
			>
				<PageHeader
					title="Departments"
					:total="units.total"
					:search="search"
					action-text="Add Unit"
					@action-clicked="toggle()"
					@search-entered="(value) => searchUnits(value)"
				/>

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
