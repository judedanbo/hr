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
import LeaveTypeList from "./partials/LeaveTypeList.vue";
import AddLeaveTypeForm from "./partials/AddLeaveTypeForm.vue";
import EditLeaveTypeForm from "./partials/EditLeaveTypeForm.vue";

const props = defineProps({
	leaveTypes: { type: Object, required: true },
	genders: { type: Array, default: () => [] },
});

const navigation = computed(() => useNavigation(props.leaveTypes));

const openDialog = ref(false);
const toggle = useToggle(openDialog);
const openEditDialog = ref(false);
const toggleEdit = useToggle(openEditDialog);
const openDeleteDialog = ref(false);
const toggleDelete = useToggle(openDeleteDialog);
const selected = ref(null);

const editType = (model) => {
	selected.value = model;
	toggleEdit();
};
const deleteType = (model) => {
	selected.value = model;
	toggleDelete();
};
const deleteConfirmed = () => {
	router.delete(route("leave-type.delete", { leaveType: selected.value.id }), {
		preserveScroll: true,
		onSuccess: () => toggleDelete(),
	});
};

const links = [{ name: "Leave Types", url: "" }];
</script>

<template>
	<MainLayout>
		<Head title="Leave Types" />
		<main class="max-w-7xl mx-auto sm:px-6 lg:px-8">
			<BreadCrumpVue :links="links" />
			<div
				class="overflow-hidden shadow-sm sm:rounded-lg px-6 border-b border-gray-200"
			>
				<TableHeader
					title="Leave Types"
					:total="leaveTypes.total"
					action-text="Add Leave Type"
					@action-clicked="toggle()"
				/>
				<LeaveTypeList
					:leave-types="leaveTypes.data"
					@edit-type="(model) => editType(model)"
					@delete-type="(model) => deleteType(model)"
				>
					<template #pagination>
						<Pagination :navigation="navigation" />
					</template>
				</LeaveTypeList>
			</div>
		</main>

		<Modal :show="openDialog" @close="toggle()">
			<AddLeaveTypeForm :genders="genders" @form-submitted="toggle()" />
		</Modal>
		<Modal :show="openEditDialog" @close="toggleEdit()">
			<EditLeaveTypeForm
				:leave-type="selected"
				:genders="genders"
				@form-submitted="toggleEdit()"
			/>
		</Modal>
		<Delete
			:show="openDeleteDialog"
			model-name="leave type"
			@close="toggleDelete()"
			@delete-confirmed="deleteConfirmed()"
		/>
	</MainLayout>
</template>
