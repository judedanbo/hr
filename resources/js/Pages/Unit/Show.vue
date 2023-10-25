<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, Link } from "@inertiajs/inertia-vue3";

import BreadCrumpVue from "@/Components/BreadCrump.vue";
import { ref, watch } from "vue";
import debounce from "lodash/debounce";
import { Inertia } from "@inertiajs/inertia";
import SubUnits from "./SubUnits.vue";
import UnitStaff from "./UnitStaff.vue";
let props = defineProps({
	unit: Object,
	filters: Object,
});

let search = ref(props.filters.search);

let dept = ref(props.filters.dept);
let staff = ref(props.filters.staff);

watch(
	dept,
	debounce(function (value) {
		Inertia.get(
			route("unit.show", {
				unit: props.unit.id,
			}),
			{ dept: value },
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
		name: props.unit.institution.name,
		url: route("institution.show", {
			institution: props.unit.institution.id,
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
</script>

<template>
	<Head title="Dashboard" />

	<MainLayout>
		<template #header>
			<h2 class="font-semibold text-xl text-gray-800 leading-tight">
				{{ props.unit.name }}
			</h2>
		</template>

		<div class="py-2">
			<div class="max-w-full mx-auto sm:px-6 lg:px-8">
				<BreadCrumpVue :links="BreadcrumbLinks" />
				<FormKit
					v-model="search"
					prefix-icon="search"
					type="search"
					placeholder="Search ..."
					autofocus
					outer-class="md:w-1/2 xl:w-1/3 px-4 "
				/>
				<div
					class="flex space-y-4 space-x-0 xl:space-x-4 xl:space-y-0 flex-col xl:flex-row items-start justify-center py-4"
				>
					<SubUnits v-model="dept" :type="unit.name" :subs="props.unit" />
					<UnitStaff :unit="props.unit" />
				</div>
			</div>
		</div>
	</MainLayout>
</template>
