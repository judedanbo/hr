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
import LeaveYearList from "./partials/LeaveYearList.vue";
import AddLeaveYearForm from "./partials/AddLeaveYearForm.vue";
import EditLeaveYearForm from "./partials/EditLeaveYearForm.vue";

const props = defineProps({
	leaveYears: { type: Object, required: true },
});

const navigation = computed(() => useNavigation(props.leaveYears));

const openDialog = ref(false);
const toggle = useToggle(openDialog);

const openEditDialog = ref(false);
const toggleEdit = useToggle(openEditDialog);

const openDeleteDialog = ref(false);
const toggleDelete = useToggle(openDeleteDialog);

const selected = ref(null);

const editYear = (model) => {
	selected.value = model;
	toggleEdit();
};

const deleteYear = (model) => {
	selected.value = model;
	toggleDelete();
};

const cloneYear = (model) => {
	const source = window.prompt(
		`Enter the ID of the source year to clone configuration into ${model.year}:`,
	);
	if (!source) return;
	router.post(
		route("leave-year.clone", { leaveYear: model.id }),
		{ source_leave_year_id: source },
		{ preserveScroll: true },
	);
};

const deleteConfirmed = () => {
	router.delete(route("leave-year.delete", { leaveYear: selected.value.id }), {
		preserveScroll: true,
		onSuccess: () => toggleDelete(),
	});
};

const links = [{ name: "Leave Years", url: "" }];
</script>

<template>
	<MainLayout>
		<Head title="Leave Years" />
		<main class="max-w-7xl mx-auto sm:px-6 lg:px-8">
			<BreadCrumpVue :links="links" />
			<div
				class="overflow-hidden shadow-sm sm:rounded-lg px-6 border-b border-gray-200"
			>
				<TableHeader
					title="Leave Years"
					:total="leaveYears.total"
					action-text="Add Leave Year"
					@action-clicked="toggle()"
				/>
				<LeaveYearList
					:leave-years="leaveYears.data"
					@edit-year="(model) => editYear(model)"
					@delete-year="(model) => deleteYear(model)"
					@clone-year="(model) => cloneYear(model)"
				>
					<template #pagination>
						<Pagination :navigation="navigation" />
					</template>
				</LeaveYearList>
			</div>
		</main>

		<Modal :show="openDialog" @close="toggle()">
			<AddLeaveYearForm @form-submitted="toggle()" />
		</Modal>
		<Modal :show="openEditDialog" @close="toggleEdit()">
			<EditLeaveYearForm
				:leave-year="selected"
				@form-submitted="toggleEdit()"
			/>
		</Modal>
		<Delete
			:show="openDeleteDialog"
			model-name="leave year"
			@close="toggleDelete()"
			@delete-confirmed="deleteConfirmed()"
		/>
	</MainLayout>
</template>
