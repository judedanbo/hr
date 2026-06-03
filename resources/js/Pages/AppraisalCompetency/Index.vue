<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, router, usePage } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import Pagination from "@/Components/Pagination.vue";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import Modal from "@/Components/NewModal.vue";
import TableHeader from "@/Components/TableHeader.vue";
import MainTable from "@/Components/MainTable.vue";
import TableHead from "@/Components/TableHead.vue";
import TableBody from "@/Components/TableBody.vue";
import RowHeader from "@/Components/RowHeader.vue";
import TableData from "@/Components/TableData.vue";
import TableRow from "@/Components/TableRow.vue";
import SubMenu from "@/Components/SubMenu.vue";
import { useToggle } from "@vueuse/core";
import { useNavigation } from "@/Composables/navigation";
import CompetencyForm from "./partials/CompetencyForm.vue";

const props = defineProps({
	competencies: { type: Object, required: true },
	groups: { type: Array, default: () => [] },
	jobCategories: { type: Array, default: () => [] },
	filters: { type: Object, default: () => ({}) },
});

const navigation = computed(() => useNavigation(props.competencies));
const page = usePage();
const permissions = computed(() => page.props?.auth.permissions);

const openDialog = ref(false);
const toggle = useToggle(openDialog);
const openEditDialog = ref(false);
const toggleEditDialog = useToggle(openEditDialog);
const openDeleteDialog = ref(false);
const toggleDeleteModal = useToggle(openDeleteDialog);
const selected = ref(null);

const editItem = (model) => {
	selected.value = model;
	toggleEditDialog();
};
const deleteItem = (model) => {
	selected.value = model;
	toggleDeleteModal();
};
const deleteConfirmed = () => {
	router.delete(route("appraisal-competency.delete", { appraisalCompetency: selected.value.id }), {
		preserveScroll: true,
		onSuccess: () => toggleDeleteModal(),
	});
};

const tableCols = ["Name", "Group", "Weight", "Job category", "Active", "Action"];
const links = [{ name: "Appraisal Competencies", url: "" }];
</script>

<template>
	<MainLayout>
		<Head title="Appraisal Competencies" />
		<main class="max-w-7xl mx-auto sm:px-6 lg:px-8">
			<BreadCrumpVue :links="links" />
			<div class="overflow-hidden shadow-sm sm:rounded-lg px-6 border-b border-gray-200">
				<TableHeader
					title="Appraisal Competencies"
					:total="competencies.total"
					:search="filters.search"
					action-text="Add Competency"
					@action-clicked="toggle()"
				/>
				<section class="flex flex-col mt-6 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
					<div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
						<div class="overflow-hidden border-b border-gray-200 rounded-md shadow-md">
							<MainTable>
								<TableHead>
									<RowHeader v-for="(column, id) in tableCols" :key="id">{{ column }}</RowHeader>
								</TableHead>
								<TableBody>
									<TableRow v-for="competency in competencies.data" :key="competency.id">
										<TableData>{{ competency.name }}</TableData>
										<TableData><span :class="competency.group_color">{{ competency.group_label }}</span></TableData>
										<TableData>{{ competency.default_weight }}%</TableData>
										<TableData>{{ competency.job_category ?? "All" }}</TableData>
										<TableData>{{ competency.is_active ? "Yes" : "No" }}</TableData>
										<TableData>
											<div class="flex justify-end">
												<SubMenu
													v-if="permissions?.includes('edit appraisal competency') || permissions?.includes('delete appraisal competency')"
													:can-edit="permissions?.includes('edit appraisal competency')"
													:can-delete="permissions?.includes('delete appraisal competency')"
													:items="['Edit', 'Delete']"
													@itemClicked="(action) => (action === 'Edit' ? editItem(competency) : deleteItem(competency))"
												/>
											</div>
										</TableData>
									</TableRow>
								</TableBody>
							</MainTable>
							<Pagination :navigation="navigation" />
						</div>
					</div>
				</section>
			</div>
		</main>

		<Modal :show="openDialog" @close="toggle()">
			<CompetencyForm :groups="groups" :job-categories="jobCategories" @form-submitted="toggle()" />
		</Modal>
		<Modal :show="openEditDialog" @close="toggleEditDialog()">
			<CompetencyForm :competency="selected" :groups="groups" :job-categories="jobCategories" @form-submitted="toggleEditDialog()" />
		</Modal>
		<Modal :show="openDeleteDialog" @close="toggleDeleteModal()">
			<main class="px-8 py-8 bg-gray-100 dark:bg-gray-700">
				<h3 class="text-base font-semibold text-gray-900 dark:text-gray-50">Delete competency</h3>
				<p class="mt-2 text-sm text-gray-500 dark:text-gray-200">Are you sure you want to delete this competency?</p>
				<div class="mt-5 sm:flex sm:flex-row-reverse">
					<button type="button" class="inline-flex w-full justify-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:ml-3 sm:w-auto" @click="deleteConfirmed()">Delete</button>
					<button type="button" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto" @click="toggleDeleteModal()">Cancel</button>
				</div>
			</main>
		</Modal>
	</MainLayout>
</template>
