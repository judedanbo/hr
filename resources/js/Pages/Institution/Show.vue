<script setup>
import NewLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, Link } from "@inertiajs/inertia-vue3";

import { Inertia } from "@inertiajs/inertia";
import { PlusIcon } from "@heroicons/vue/24/outline";

import { ref, watch } from "vue";
import debounce from "lodash/debounce";
import UnitCard from "../Unit/UnitCard.vue";
import Modal from "@/Components/NewModal.vue";

import { useToggle } from "@vueuse/core";
import PageNav from "@/Components/PageNav.vue";
import AddForm from "./partials/AddForm.vue";
import EditForm from "./partials/EditForm.vue";
import DeleteUnit from "../Unit/Delete.vue";

let props = defineProps({
	institution: Object,
	departments: Array,
	filters: Object,
});

let open = ref(false);
let openEditForm = ref(false);
let openDeleteModal = ref(false);

let toggle = useToggle(open);
let toggleEditForm = useToggle(openEditForm);
let toggleDeleteModal = useToggle(openDeleteModal);

let selectedUnit = ref(null);

const newDepartment = () => {
	toggle();
};

const closeModal = () => {
	toggleEditForm();
};
const closeEditModal = () => {
	form.reset();
	toggle();
};

const navMenu = [
	{ name: "Departments", href: "", active: true },
	{ name: "staff", href: "#", active: false },
	{ name: "Heads", href: "#", active: false },
	{ name: "Units", href: "#", active: false },
];

let editDepartment = (id) => {
	selectedUnit.value = props.departments.find(
		(department) => department.id === id,
	);
	toggleEditForm();
};

let deleteUnit = (id) => {
	selectedUnit.value = props.departments.find(
		(department) => department.id === id,
	);
	toggleDeleteModal();
};

let search = ref(props.filters.search);

watch(
	search,
	debounce(function (value) {
		Inertia.get(
			route("institution.show", {
				institution: props.institution.id,
			}),
			{ search: value },
			{ preserveState: true, replace: true, preserveScroll: true },
		);
	}, 300),
);
</script>

<template>
	<Head v-if="institution" :title="institution.name" />

	<NewLayout>
		<main class="w-full px-8">
			<div class="relative isolate overflow-hidden">
				<!-- Secondary navigation -->
				<div class="flex justify-between items-center">
					<!-- <PageNav :pageMenu="navMenu" /> -->
					<a
						@click.prevent="newDepartment()"
						href="#"
						class="ml-auto flex items-center gap-x-1 rounded-md bg-green-600 dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600"
					>
						<PlusIcon class="-ml-1.5 h-5 w-5" aria-hidden="true" />
						New unit
					</a>
				</div>

				<!-- Stats -->
				<div
					class="border-b border-b-gray-900/10 lg:border-t lg:border-t-gray-900/5"
				>
					<dl
						class="mx-auto grid max-w-7xl grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 lg:px-2 xl:px-0"
					>
						<div
							class="flex items-baseline flex-wrap justify-between gap-y-2 gap-x-4 border-t border-gray-900/5 px-4 py-10 sm:px-6 lg:border-t-0 xl:px-8"
						>
							<dt
								class="text-sm font-medium leading-6 text-gray-500 dark:text-gray-50"
							>
								Departments
							</dt>
							<dd class="text-gray-700 dark:text-white', 'text-xs font-medium">
								0.5%
							</dd>
							<dd
								class="w-full flex-none text-3xl font-medium leading-10 tracking-tight text-gray-900 dark:text-gray-50"
							>
								{{ institution.departments.toLocaleString() }}
							</dd>
						</div>
						<div
							class="flex items-baseline flex-wrap justify-between gap-y-2 gap-x-4 border-t border-gray-900/5 px-4 py-10 sm:px-6 lg:border-t-0 xl:px-8"
						>
							<dt
								class="text-sm font-medium leading-6 text-gray-500 dark:text-gray-50"
							>
								Divisions
							</dt>
							<dd class="text-gray-700', 'text-xs font-medium">0.5%</dd>
							<dd
								class="w-full flex-none text-3xl font-medium leading-10 tracking-tight text-gray-900 dark:text-gray-50"
							>
								{{ institution.divisions.toLocaleString() }}
							</dd>
						</div>
						<div
							class="flex items-baseline flex-wrap justify-between gap-y-2 gap-x-4 border-t border-gray-900/5 px-4 py-10 sm:px-6 lg:border-t-0 xl:px-8"
						>
							<dt
								class="text-sm font-medium leading-6 text-gray-500 dark:text-gray-50"
							>
								Units
							</dt>
							<dd class="text-gray-700', 'text-xs font-medium">0.5%</dd>
							<dd
								class="w-full flex-none text-3xl font-medium leading-10 tracking-tight text-gray-900 dark:text-gray-50"
							>
								{{ institution.units.toLocaleString() }}
							</dd>
						</div>
						<div
							class="flex items-baseline flex-wrap justify-between gap-y-2 gap-x-4 border-t border-gray-900/5 px-4 py-10 sm:px-6 lg:border-t-0 xl:px-8"
						>
							<dt
								class="text-sm font-medium leading-6 text-gray-500 dark:text-gray-50"
							>
								Staff
							</dt>
							<dd class="text-gray-700', 'text-xs font-medium">0.5%</dd>
							<dd
								class="w-full flex-none text-3xl font-medium leading-10 tracking-tight text-gray-900 dark:text-gray-50"
							>
								{{ institution.staff.toLocaleString() }}
							</dd>
						</div>
					</dl>
				</div>

				<div
					class="absolute left-0 top-full -z-10 mt-96 origin-top-left translate-y-40 -rotate-90 transform-gpu opacity-20 blur-3xl sm:left-1/2 sm:-ml-96 sm:-mt-10 sm:translate-y-0 sm:rotate-0 sm:transform-gpu sm:opacity-50"
					aria-hidden="true"
				>
					<div
						class="aspect-[1154/678] w-[72.125rem] bg-gradient-to-br from-[#0adb3b] to-[#9089FC] dark:from-white dark:to-white"
						style="
							clip-path: polygon(
								100% 38.5%,
								82.6% 100%,
								60.2% 37.7%,
								52.4% 32.1%,
								47.5% 41.8%,
								45.2% 65.6%,
								27.5% 23.4%,
								0.1% 35.3%,
								17.9% 0%,
								27.7% 23.4%,
								76.2% 2.5%,
								74.2% 56%,
								100% 38.5%
							);
						"
					/>
				</div>
			</div>

			<div class="space-y-16 py-8">
				<!-- department list-->
				<div
					class="mx-auto max-w-7xl pt-4 pb-12 px-4 sm:px-6 lg:px-8 bg-white dark:bg-gray-700 rounded-lg"
				>
					<div class="mx-auto max-w-2xl lg:mx-0 lg:max-w-none">
						<div class="flex items-center justify-between">
							<h2
								class="text-base font-semibold leading-7 text-gray-900 dark:text-gray-50"
							>
								Departments
							</h2>
						</div>
						<ul
							role="list"
							class="mt-6 grid grid-cols-1 gap-x-6 gap-y-8 lg:grid-cols-3 xl:gap-x-8"
						>
							<template v-for="department in departments" :key="department.id">
								<UnitCard
									@editItem="(id) => editDepartment(id)"
									@delete-item="(id) => deleteUnit(id)"
									:unit="department"
								/>
							</template>
						</ul>
					</div>
				</div>
			</div>
			<Modal @close="toggle()" :show="open">
				<AddForm
					:institutionName="institution.name"
					:institutionId="institution.id"
				/>
			</Modal>
			<Modal @close="toggleEditForm()" :show="openEditForm">
				<!-- {{ selectedUnit }} -->
				<EditForm
					@formSubmitted="toggleEditForm()"
					:institutionName="institution.name"
					:institutionId="institution.id"
					:unit="selectedUnit"
				/>
			</Modal>
			<Modal @close="toggleDeleteModal()" :show="openDeleteModal">
				<DeleteUnit
					@unit-deleted="toggleDeleteModal()"
					:selectedModel="selectedUnit"
				/>
			</Modal>
		</main>
	</NewLayout>
</template>
