<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head } from "@inertiajs/inertia-vue3";
import { ref, computed } from "vue";
import { Inertia } from "@inertiajs/inertia";
import Pagination from "@/Components/Pagination.vue";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import Modal from "@/Components/NewModal.vue";
import AddPositionForm from "./partials/AddPositionForm.vue";
import EditPositionForm from "./partials/EditPositionForm.vue";
import { useToggle } from "@vueuse/core";
import TableHeader from "@/Components/TableHeader.vue";
import PositionList from "./partials/PositionList.vue";
import { useNavigation } from "@/Composables/navigation";
import Delete from "./Delete.vue";
import { useSearch } from "@/Composables/search";

const navigation = computed(() => useNavigation(props.positions));

let props = defineProps({
	positions: { type: Object, required: true },
	filters: { type: Object, default: () => {} },
});

const openEditDialog = ref(false);

let toggleEditDialog = useToggle(openEditDialog);

const selectedPosition = ref(null);

const editDialog = (model) => {
	selectedPosition.value = model;
	// console.log(model);
	toggleEditDialog();
};

const openDeleteDialog = ref(false);

const deletePosition = (model) => {
	selectedPosition.value = model;
	toggleDeleteModal();
};

let toggleDeleteModal = useToggle(openDeleteDialog);

let openDialog = ref(false);

let toggle = useToggle(openDialog);

const searchPosition = (value) => {
	useSearch(value, route("position.index"));
};

let openPosition = (position) => {
	console.log(position);
	Inertia.visit(route("position.show", { position: position }));
};

let BreadCrumpLinks = [
	{
		name: "Positions",
		url: "",
	},
];
const deletePositionConfirmed = (position) => {
	Inertia.delete(route("position.delete", { position: position }), {
		PreserveScroll: true,
		onSuccess: () => {
			toggleDeleteModal();
		},
	});
};
</script>

<template>
	<MainLayout>
		<Head title="Positions" />
		<main class="max-w-7xl mx-auto sm:px-6 lg:px-8">
			<BreadCrumpVue :links="BreadCrumpLinks" />
			<div
				class="overflow-hidden shadow-sm sm:rounded-lg px-6 border-b border-gray-200"
			>
				<TableHeader
					title="Positions"
					:total="positions.total"
					:search="filters.search"
					class="w-4/6"
					action-text="Create Position"
					@action-clicked="toggle()"
					@search-entered="(value) => searchPosition(value)"
				/>

				<!-- <div class="flex gap-x-5">
					<a
						class="rounded-md flex gap-x-3 bg-green-600 dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600"
						:href="route('report.staff')"
					>
						<arrow-down-tray-icon class="-ml-1.5 h-5 w-5" aria-hidden="true" />
						Staff position
					</a>
					<a
						class="rounded-md flex gap-x-3 bg-green-600 dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600"
						:href="route('report.staff-details')"
					>
						<arrow-down-tray-icon class="-ml-1.5 h-5 w-5" aria-hidden="true" />
						Staff details
					</a>
					<a
						class="rounded-md flex gap-x-3 bg-green-600 dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600"
						:href="route('report.staff-retirement')"
					>
						<arrow-down-tray-icon class="-ml-1.5 h-5 w-5" aria-hidden="true" />
						Staff retirement
					</a>
					<a
						class="rounded-md flex gap-x-3 bg-green-600 dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600"
						:href="route('report.staff-pending-transfer')"
					>
						<arrow-down-tray-icon class="-ml-1.5 h-5 w-5" aria-hidden="true" />
						Pending Transfer
					</a>
				</div> -->

				<PositionList
					:positions="positions.data"
					@open-position="(positionId) => openPosition(positionId)"
					@edit-position="(model) => editDialog(model)"
					@delete-position="(model) => deletePosition(model)"
				>
					<template #pagination>
						<Pagination :navigation="navigation" />
					</template>
				</PositionList>
			</div>
		</main>
		<Modal :show="openDialog" @close="toggle()">
			<AddPositionForm @form-submitted="toggle()" />
		</Modal>
		<Modal :show="openEditDialog" @close="toggleEditDialog()">
			<EditPositionForm
				:position="selectedPosition"
				@form-submitted="toggleEditDialog()"
			/>
		</Modal>
		<Modal :show="openDeleteDialog" @close="toggleDeleteModal()">
			<Delete
				@cancel-delete="toggleDeleteModal()"
				@deleted-position="deletePositionConfirmed(selectedPosition.id)"
			/>
		</Modal>
	</MainLayout>
</template>
