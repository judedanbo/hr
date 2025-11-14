<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, Link, usePage } from "@inertiajs/vue3";
import PageHeader from "@/Components/PageHeader.vue";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import { ref, watch, computed } from "vue";
import { debouncedWatch } from "@vueuse/core";
import { router } from "@inertiajs/vue3";
import SubUnits from "./SubUnits.vue";
import UnitStaff from "./UnitStaff.vue";
import InfoCard from "@/Components/InfoCard.vue";
import { PencilSquareIcon, PlusIcon } from "@heroicons/vue/20/solid";
import Modal from "@/Components/NewModal.vue";
import { useToggle } from "@vueuse/core";
import EditUnit from "./partials/Edit.vue";
import AddSubUnit from "./partials/AddSubUnit.vue";
import { download } from "@formkit/icons";

let props = defineProps({
	unit: Object,
	filters: Object,
});

const page = usePage();
const permissions = computed(() => page.props?.auth.permissions);

let search = ref(props.filters.search);

let dept = ref(props.filters.dept);
let staff = ref(props.filters.staff);

debouncedWatch(
	search,
	() => {
		router.get(
			route("unit.show", {
				unit: props.unit.id,
			}),
			{ search: search.value },
			{ preserveState: true, replace: true, preserveScroll: true },
		);
	},
	{ debounce: 300 },
);
debouncedWatch(
	staff,
	() => {
		router.get(
			route("unit.show", {
				unit: props.unit.id,
			}),
			{ staff: staf.value },
			{ preserveState: true, replace: true, preserveScroll: true },
		);
	},
	{ debounce: 300 },
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
const openAddSubUnitModal = ref(false);
const toggleAddUnitForm = useToggle(openAddSubUnitModal);
</script>

<template>
	<Head :title="props.unit.name" />

	<MainLayout>
		<!-- <template #header>
			<h2 class="font-semibold text-xl text-gray-800 leading-tight">
				{{ props.unit.name }}
			</h2>
		</template> -->
		<main class="max-w-7xl mx-auto">
			<BreadCrumpVue :links="BreadcrumbLinks" />
			<div class="text-3xl m-4 text-gray-900 dark:text-gray-50">
				{{ unit.name }}
				<span v-if="unit.type" class="block text-xl">{{ unit.type }}</span>
			</div>
			<div class="shadow-sm sm:rounded-lg px-6 mt-2 border-b border-gray-200">
				<section class="sm:flex items-center justify-between my-2">
					<FormKit
						v-model="search"
						prefix-icon="search"
						type="search"
						placeholder="`Search unit...`"
						autofocus
					/>
					<div class="flex gap-x-2">
						<a
							v-if="permissions?.includes('edit unit')"
							class="ml-auto flex items-center gap-x-1 rounded-md bg-green-600 dark:bg-gray-800 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-green-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600 dark:hover:bg-gray-900"
							href="#"
							@click.prevent="toggleEditForm()"
						>
							<PencilSquareIcon class="-ml-1.5 h-5 w-5" aria-hidden="true" />
							Edit Unit
						</a>
						<a
							v-if="permissions?.includes('create unit')"
							class="ml-auto flex items-center gap-x-1 rounded-md bg-green-600 dark:bg-gray-800 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-green-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600 dark:hover:bg-gray-900"
							href="#"
							@click.prevent="toggleAddUnitForm()"
						>
							<PlusIcon class="-ml-1.5 h-5 w-5" aria-hidden="true" />
							Add sub Unit
						</a>
					</div>
				</section>

				<div class="flex flex-col items-center justify-center gap-x-4">
					<SubUnits
						v-if="unit.subs.length > 0"
						v-model="dept"
						:download="permissions?.includes('download unit staff')"
						:type="unit.name"
						:subs="props.unit"
					/>
					<UnitStaff
						:download="permissions?.includes('download unit staff')"
						:unit="props.unit"
					/>
				</div>
			</div>
		</main>
		<Modal :show="openEditModal" @close="toggleEditForm()">
			<EditUnit :unit="props.unit.id" @form-submitted="toggleEditForm()" />
		</Modal>
		<Modal :show="openAddSubUnitModal" @close="toggleAddUnitForm()">
			<!-- subunit -->
			<AddSubUnit :unit="props.unit.id" @form-submitted="toggleAddUnitForm()" />
		</Modal>
	</MainLayout>
</template>
