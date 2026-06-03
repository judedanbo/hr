<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, router, usePage } from "@inertiajs/vue3";
import { ref, computed } from "vue";
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
import RatingLevelForm from "./partials/RatingLevelForm.vue";

const props = defineProps({
	levels: { type: Array, required: true },
});

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
	router.delete(route("appraisal-rating-level.delete", { appraisalRatingLevel: selected.value.id }), {
		preserveScroll: true,
		onSuccess: () => toggleDeleteModal(),
	});
};

const tableCols = ["Value", "Label", "Score band", "Description", "Action"];
const links = [{ name: "Appraisal Rating Levels", url: "" }];
</script>

<template>
	<MainLayout>
		<Head title="Appraisal Rating Levels" />
		<main class="max-w-7xl mx-auto sm:px-6 lg:px-8">
			<BreadCrumpVue :links="links" />
			<div class="overflow-hidden shadow-sm sm:rounded-lg px-6 border-b border-gray-200">
				<TableHeader
					title="Rating Levels"
					:total="levels.length"
					action-text="Add Level"
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
									<TableRow v-for="level in levels" :key="level.id">
										<TableData>{{ level.value }}</TableData>
										<TableData><span :class="level.color">{{ level.label }}</span></TableData>
										<TableData>{{ level.min_score }} – {{ level.max_score }}</TableData>
										<TableData>{{ level.description }}</TableData>
										<TableData>
											<div class="flex justify-end">
												<SubMenu
													v-if="permissions?.includes('edit appraisal rating level') || permissions?.includes('delete appraisal rating level')"
													:can-edit="permissions?.includes('edit appraisal rating level')"
													:can-delete="permissions?.includes('delete appraisal rating level')"
													:items="['Edit', 'Delete']"
													@itemClicked="(action) => (action === 'Edit' ? editItem(level) : deleteItem(level))"
												/>
											</div>
										</TableData>
									</TableRow>
								</TableBody>
							</MainTable>
						</div>
					</div>
				</section>
			</div>
		</main>

		<Modal :show="openDialog" @close="toggle()">
			<RatingLevelForm @form-submitted="toggle()" />
		</Modal>
		<Modal :show="openEditDialog" @close="toggleEditDialog()">
			<RatingLevelForm :level="selected" @form-submitted="toggleEditDialog()" />
		</Modal>
		<Modal :show="openDeleteDialog" @close="toggleDeleteModal()">
			<main class="px-8 py-8 bg-gray-100 dark:bg-gray-700">
				<h3 class="text-base font-semibold text-gray-900 dark:text-gray-50">Delete rating level</h3>
				<p class="mt-2 text-sm text-gray-500 dark:text-gray-200">Are you sure you want to delete this rating level?</p>
				<div class="mt-5 sm:flex sm:flex-row-reverse">
					<button type="button" class="inline-flex w-full justify-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:ml-3 sm:w-auto" @click="deleteConfirmed()">Delete</button>
					<button type="button" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto" @click="toggleDeleteModal()">Cancel</button>
				</div>
			</main>
		</Modal>
	</MainLayout>
</template>
