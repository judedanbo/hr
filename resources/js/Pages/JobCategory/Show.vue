<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, usePage } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import { useToggle } from "@vueuse/core";
import Modal from "@/Components/NewModal.vue";
import JobCategory from "./partials/JobCategory.vue";
import AddJobsToCategory from "./partials/AddJobsToCategory.vue";
import EditJobsToCategory from "./partials/EditJobsToCategory.vue";
import DeleteCategory from "./partials/DeleteCategory.vue";
import { router } from "@inertiajs/vue3";
import NoPermission from "@/Components/NoPermission.vue";

let openAddDialog = ref(false);

let toggle = useToggle(openAddDialog);

const page = usePage();
const permissions = computed(() => page.props?.auth.permissions);

let openEditDialog = ref(false);
const toggleEditCategory = useToggle(openEditDialog);

let openConfirmDeleteDialog = ref(false);
const toggleConfirmDeleteCategory = useToggle(openConfirmDeleteDialog);

const deleteCategory = () => {
	router.delete(
		route("job-category.delete", {
			jobCategory: props.category.id,
		}),
		{
			preserveScroll: true,
			onSuccess: () => {
				toggleConfirmDeleteCategory();
				router.visit(route("job-category.index"));
			},
		},
	);
};
let props = defineProps({
	category: { type: Object, required: true },
	filters: { type: Object, default: () => {} },
});

let BreadCrumpLinks = [
	{
		name: "Ranks Categories",
	},
];
</script>

<template>
	<Head title="Harmonized Categories" />

	<MainLayout>
		<div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
			<div
				v-if="permissions?.includes('view job category')"
				class="overflow-hidden shadow-sm sm:rounded-lg"
			>
				<div class="p-2 border-b border-gray-200">
					<BreadCrumpVue :links="BreadCrumpLinks" />
					<h2 class="text-3xl text-gray-900 dark:text-gray-50 my-4">
						Ranks/Grades Categories
					</h2>
					<JobCategory
						:category="category"
						@add-rank="toggle()"
						@edit-rank="toggleEditCategory()"
						@delete-rank="toggleConfirmDeleteCategory()"
					/>
				</div>
			</div>
			<NoPermission v-else />
		</div>
		<Modal :show="openAddDialog" @close="toggle()">
			<AddJobsToCategory @form-submitted="toggle()" />
		</Modal>
		<Modal :show="openEditDialog" @close="toggleEditCategory()">
			<EditJobsToCategory
				:category="category"
				@form-submitted="toggleEditCategory()"
			/>
		</Modal>
		<Modal
			:show="openConfirmDeleteDialog"
			@close="toggleConfirmDeleteCategory()"
		>
			<DeleteCategory
				:category="category"
				@close="toggleConfirmDeleteCategory()"
				@delete-confirmed="deleteCategory()"
			/>
		</Modal>
	</MainLayout>
</template>
