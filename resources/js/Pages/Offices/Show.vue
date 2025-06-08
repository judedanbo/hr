<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, usePage } from "@inertiajs/inertia-vue3";
import { ref, computed } from "vue";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import { useToggle } from "@vueuse/core";
import Modal from "@/Components/NewModal.vue";
import Region from "./partials/Region.vue";
// import AddJobsToCategory from "./partials/AddJobsToCategory.vue";
// import EditJobsToCategory from "./partials/EditJobsToCategory.vue";
// import DeleteCategory from "./partials/DeleteCategory.vue";
import { Inertia } from "@inertiajs/inertia";
import NoPermission from "@/Components/NoPermission.vue";
import Units from "./partials/Units.vue";
import { useNavigation } from "@/Composables/navigation";
import Pagination from "@/Components/Pagination.vue";

let openAddDialog = ref(false);

let toggle = useToggle(openAddDialog);

const page = usePage();
const permissions = computed(() => page.props.value.auth.permissions);

let openEditDialog = ref(false);
const toggleEditCategory = useToggle(openEditDialog);

let openConfirmDeleteDialog = ref(false);
const toggleConfirmDeleteCategory = useToggle(openConfirmDeleteDialog);

const deleteCategory = () => {
	Inertia.delete(
		route("job-category.delete", {
			jobCategory: props.category.id,
		}),
		{
			preserveScroll: true,
			onSuccess: () => {
				toggleConfirmDeleteCategory();
				Inertia.visit(route("job-category.index"));
			},
		},
	);
};
let props = defineProps({
	office: { type: Object, required: true },
	filters: { type: Object, default: () => {} },
});

// const navigation = computed(() => useNavigation(props.offices));

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
			<div class="overflow-hidden shadow-sm sm:rounded-lg">
				<div class="p-2 border-b border-gray-200">
					<BreadCrumpVue :links="BreadCrumpLinks" />
					<h2 class="text-3xl text-gray-900 dark:text-gray-50 my-4">
						Office: {{ office.name }}
					</h2>
					<Units :units="office.units">
						<Pagination :navigation="navigation" />
					</Units>
				</div>
			</div>
			<!-- <NoPermission v-else /> -->
		</div>
		<Modal :show="openAddDialog" @close="toggle()">
			<AddJobsToCategory @form-submitted="toggle()" />
		</Modal>
	</MainLayout>
</template>
