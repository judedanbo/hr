<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";
import Pagination from "@/Components/Pagination.vue";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import Modal from "@/Components/NewModal.vue";
import Delete from "@/Components/Delete.vue";
import TableHeader from "@/Components/TableHeader.vue";
import { useToggle } from "@vueuse/core";
import { useNavigation } from "@/Composables/navigation";
import PlanningWindowList from "./partials/PlanningWindowList.vue";
import AddPlanningWindowForm from "./partials/AddPlanningWindowForm.vue";
import EditPlanningWindowForm from "./partials/EditPlanningWindowForm.vue";

const props = defineProps({
	windows: { type: Object, required: true },
	leaveYears: { type: Array, default: () => [] },
});

const navigation = computed(() => useNavigation(props.windows));

const openDialog = ref(false);
const toggle = useToggle(openDialog);
const openEditDialog = ref(false);
const toggleEdit = useToggle(openEditDialog);
const openDeleteDialog = ref(false);
const toggleDelete = useToggle(openDeleteDialog);
const selected = ref(null);

const editWindow = (model) => {
	selected.value = model;
	toggleEdit();
};
const deleteWindow = (model) => {
	selected.value = model;
	toggleDelete();
};
const deleteConfirmed = () => {
	router.delete(
		route("leave-planning-window.delete", {
			leavePlanningWindow: selected.value.id,
		}),
		{ preserveScroll: true, onSuccess: () => toggleDelete() },
	);
};

const links = [{ name: "Planning Windows", url: "" }];
</script>

<template>
	<MainLayout>
		<Head title="Planning Windows" />
		<main class="max-w-7xl mx-auto sm:px-6 lg:px-8">
			<BreadCrumpVue :links="links" />
			<div
				class="overflow-hidden shadow-sm sm:rounded-lg px-6 border-b border-gray-200"
			>
				<TableHeader
					title="Planning Windows"
					:total="windows.total"
					action-text="Add Window"
					@action-clicked="toggle()"
				/>
				<PlanningWindowList
					:windows="windows.data"
					@edit-window="(model) => editWindow(model)"
					@delete-window="(model) => deleteWindow(model)"
				>
					<template #pagination>
						<Pagination :navigation="navigation" />
					</template>
				</PlanningWindowList>
			</div>
		</main>

		<Modal :show="openDialog" @close="toggle()">
			<AddPlanningWindowForm
				:leave-years="leaveYears"
				@form-submitted="toggle()"
			/>
		</Modal>
		<Modal :show="openEditDialog" @close="toggleEdit()">
			<EditPlanningWindowForm
				:window="selected"
				:leave-years="leaveYears"
				@form-submitted="toggleEdit()"
			/>
		</Modal>
		<Delete
			:show="openDeleteDialog"
			model-name="planning window"
			@close="toggleDelete()"
			@delete-confirmed="deleteConfirmed()"
		/>
	</MainLayout>
</template>
