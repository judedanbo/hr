<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, router } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import { useToggle } from "@vueuse/core";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import Pagination from "@/Components/Pagination.vue";
import Modal from "@/Components/NewModal.vue";
import Delete from "@/Components/Delete.vue";
import TableHeader from "@/Components/TableHeader.vue";
import MainTable from "@/Components/MainTable.vue";
import TableHead from "@/Components/TableHead.vue";
import TableBody from "@/Components/TableBody.vue";
import RowHeader from "@/Components/RowHeader.vue";
import TableData from "@/Components/TableData.vue";
import TableRow from "@/Components/TableRow.vue";
import { useNavigation } from "@/Composables/navigation";
import DelegationForm from "./partials/DelegationForm.vue";

const props = defineProps({
	delegations: { type: Object, required: true },
	staffOptions: { type: Array, default: () => [] },
});

const navigation = computed(() => useNavigation(props.delegations));
const openAdd = ref(false);
const toggleAdd = useToggle(openAdd);
const openEdit = ref(false);
const toggleEdit = useToggle(openEdit);
const openDelete = ref(false);
const toggleDelete = useToggle(openDelete);
const selected = ref(null);

const editRow = (row) => {
	selected.value = row;
	toggleEdit();
};
const deleteRow = (row) => {
	selected.value = row;
	toggleDelete();
};
const deleteConfirmed = () => {
	router.delete(
		route("leave-delegation.delete", { leaveDelegation: selected.value.id }),
		{
			preserveScroll: true,
			onSuccess: () => toggleDelete(),
		},
	);
};

const links = [{ name: "Delegations", url: "" }];
const tableCols = ["Head", "Delegate", "From", "To", "Active", "Action"];
</script>

<template>
	<MainLayout>
		<Head title="Approval Delegations" />
		<main class="max-w-7xl mx-auto sm:px-6 lg:px-8">
			<BreadCrumpVue :links="links" />
			<div
				class="overflow-hidden shadow-sm sm:rounded-lg px-6 border-b border-gray-200"
			>
				<TableHeader
					title="Approval Delegations"
					:total="delegations.total"
					action-text="Add Delegation"
					@action-clicked="toggleAdd()"
				/>
				<section
					class="flex flex-col mt-6 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8"
				>
					<div
						class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8"
					>
						<div
							class="overflow-hidden border-b border-gray-200 rounded-md shadow-md"
						>
							<MainTable>
								<TableHead>
									<template v-for="(column, id) in tableCols" :key="id">
										<RowHeader>{{ column }}</RowHeader>
									</template>
								</TableHead>
								<TableBody>
									<template v-for="row in delegations.data" :key="row.id">
										<TableRow>
											<TableData>{{ row.delegator }}</TableData>
											<TableData>{{ row.delegate }}</TableData>
											<TableData>{{ row.start_date }}</TableData>
											<TableData>{{ row.end_date }}</TableData>
											<TableData>{{ row.is_active ? "Yes" : "No" }}</TableData>
											<TableData>
												<div class="flex justify-end gap-x-3 text-sm">
													<button
														type="button"
														class="text-green-700 hover:underline"
														@click="editRow(row)"
													>
														Edit
													</button>
													<button
														type="button"
														class="text-red-700 hover:underline"
														@click="deleteRow(row)"
													>
														Delete
													</button>
												</div>
											</TableData>
										</TableRow>
									</template>
								</TableBody>
							</MainTable>
							<Pagination :navigation="navigation" />
						</div>
					</div>
				</section>
			</div>
		</main>

		<Modal :show="openAdd" @close="toggleAdd()">
			<DelegationForm
				mode="create"
				:staff-options="staffOptions"
				@form-submitted="toggleAdd()"
			/>
		</Modal>
		<Modal :show="openEdit" @close="toggleEdit()">
			<DelegationForm
				mode="edit"
				:delegation="selected"
				:staff-options="staffOptions"
				@form-submitted="toggleEdit()"
			/>
		</Modal>
		<Delete
			:show="openDelete"
			model-name="delegation"
			@close="toggleDelete()"
			@delete-confirmed="deleteConfirmed()"
		/>
	</MainLayout>
</template>
