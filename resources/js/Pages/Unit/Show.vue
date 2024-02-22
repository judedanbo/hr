<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, Link } from "@inertiajs/inertia-vue3";
import PageHeader from "@/Components/PageHeader.vue";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import { ref, watch } from "vue";
import debounce from "lodash/debounce";
import { Inertia } from "@inertiajs/inertia";
import SubUnits from "./SubUnits.vue";
import UnitStaff from "./UnitStaff.vue";
import InfoCard from "@/Components/InfoCard.vue";
import { PencilSquareIcon } from "@heroicons/vue/20/solid";
import Modal from "@/Components/NewModal.vue";
import { useToggle } from "@vueuse/core";
import EditUnit from "./partials/Edit.vue";

let props = defineProps({
	unit: Object,
	filters: Object,
});

let search = ref(props.filters.search);

let dept = ref(props.filters.dept);
let staff = ref(props.filters.staff);

watch(
	search,
	debounce(function (value) {
		Inertia.get(
			route("unit.show", {
				unit: props.unit.id,
			}),
			{ search: value },
			{ preserveState: true, replace: true, preserveScroll: true },
		);
	}, 300),
);
watch(
	staff,
	debounce(function (value) {
		Inertia.get(
			route("unit.show", {
				unit: props.unit.id,
			}),
			{ staff: value },
			{ preserveState: true, replace: true, preserveScroll: true },
		);
	}, 300),
);

let num = 1;

//
let BreadcrumbLinks = [
	{
		name: props.unit?.institution?.name,
		url: route("institution.show", {
			institution: props.unit?.institution?.id,
		}),
	},
	{
		name: "Departments",
		url: route("unit.index", {
			institution: props.unit.institution.id,
		}),
	},
	{
		name: props.unit.parent != null ? props.unit.parent.name : null,
		url: route("unit.show", {
			unit: props.unit.parent != null ? props.unit.parent.id : 99999, //use 99999 and unit id if the unit has no parent
		}),
	},
	{ name: props.unit.name },
];
const openEditModal = ref(false);
const toggleEditForm = useToggle(openEditModal);
</script>

<template>
	<Head :title="props.unit.name" />

	<MainLayout>
		<!-- <template #header>
			<h2 class="font-semibold text-xl text-gray-800 leading-tight">
				{{ props.unit.name }}
			</h2>
		</template> -->
		<main class="max-w-7xl mx-auto sm:px-6 lg:px-8 ">
			<BreadCrumpVue :links="BreadcrumbLinks" />
			<div
				class="shadow-sm sm:rounded-lg px-6 border-b border-gray-200"
			>
				<div class="flex">
					<InfoCard title="Units" :value="props.unit.subs_number" link="#" />
					<InfoCard title="Staff" :value="props.unit.staff_number" link="#" />
				</div>
				<section class="sm:flex items-center justify-between my-2">
					<FormKit
						v-model="search"
						prefix-icon="search"
						type="search"
						placeholder="`Search unit...`"
						autofocus
					/>
					<a
						class="ml-auto flex items-center gap-x-1 rounded-md bg-green-600 dark:bg-gray-800 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-green-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600 dark:hover:bg-gray-900"
						href="#"
						@click.prevent="toggleEditForm()"
					>
						<PencilSquareIcon class="-ml-1.5 h-5 w-5" aria-hidden="true" />
						Edit Unit
					</a>
				</section>
				<div
					class="sm:flex flex-col xl:flex-row items-start justify-evenly min-w-full gap-x-12"
				>
					<SubUnits
						
						v-model="dept"
						:type="unit.name"
						:subs="props.unit"
					/>
					<UnitStaff
						
						:unit="props.unit"
					/>

				</div>
			</div>
		</main>
		<Modal :show="openEditModal" @close="toggleEditForm()"  >
			<EditUnit :unit="props.unit.id" @form-submitted="toggleEditForm()" />
		</Modal>
	</MainLayout>
</template>
