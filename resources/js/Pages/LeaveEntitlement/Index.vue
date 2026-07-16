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
import { useSearch } from "@/Composables/search";
import LeaveEntitlementList from "./partials/LeaveEntitlementList.vue";
import AddLeaveEntitlementForm from "./partials/AddLeaveEntitlementForm.vue";
import EditLeaveEntitlementForm from "./partials/EditLeaveEntitlementForm.vue";

const props = defineProps({
	entitlements: { type: Object, required: true },
	leaveYears: { type: Array, default: () => [] },
	leaveTypes: { type: Array, default: () => [] },
	jobCategories: { type: Array, default: () => [] },
	filters: { type: Object, default: () => ({}) },
});

const search = (value) => useSearch(value, route("leave-entitlement.index"));

const navigation = computed(() => useNavigation(props.entitlements));

const openDialog = ref(false);
const toggle = useToggle(openDialog);
const openEditDialog = ref(false);
const toggleEdit = useToggle(openEditDialog);
const openDeleteDialog = ref(false);
const toggleDelete = useToggle(openDeleteDialog);
const selected = ref(null);

const editRow = (model) => {
	selected.value = model;
	toggleEdit();
};
const deleteRow = (model) => {
	selected.value = model;
	toggleDelete();
};
const deleteConfirmed = () => {
	router.delete(
		route("leave-entitlement.delete", { leaveEntitlement: selected.value.id }),
		{
			preserveScroll: true,
			onSuccess: () => toggleDelete(),
		},
	);
};

const links = [{ name: "Leave Entitlements", url: "" }];
</script>

<template>
	<MainLayout>
		<Head title="Leave Entitlements" />
		<main class="max-w-7xl mx-auto sm:px-6 lg:px-8">
			<BreadCrumpVue :links="links" />
			<div
				class="overflow-hidden shadow-sm sm:rounded-lg px-6 border-b border-gray-200"
			>
				<TableHeader
					title="Leave Entitlements"
					:total="entitlements.total"
					:search="filters.search"
					action-text="Add Entitlement"
					@action-clicked="toggle()"
					@search-entered="(value) => search(value)"
				/>
				<LeaveEntitlementList
					:entitlements="entitlements.data"
					@edit-row="(model) => editRow(model)"
					@delete-row="(model) => deleteRow(model)"
				>
					<template #pagination>
						<Pagination :navigation="navigation" />
					</template>
				</LeaveEntitlementList>
			</div>
		</main>

		<Modal :show="openDialog" @close="toggle()">
			<AddLeaveEntitlementForm
				:leave-years="leaveYears"
				:leave-types="leaveTypes"
				:job-categories="jobCategories"
				@form-submitted="toggle()"
			/>
		</Modal>
		<Modal :show="openEditDialog" @close="toggleEdit()">
			<EditLeaveEntitlementForm
				:entitlement="selected"
				:leave-years="leaveYears"
				:leave-types="leaveTypes"
				:job-categories="jobCategories"
				@form-submitted="toggleEdit()"
			/>
		</Modal>
		<Delete
			:show="openDeleteDialog"
			model-name="entitlement"
			@close="toggleDelete()"
			@delete-confirmed="deleteConfirmed()"
		/>
	</MainLayout>
</template>
