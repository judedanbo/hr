<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head } from "@inertiajs/inertia-vue3";
import { ref, computed } from "vue";
import { Inertia } from "@inertiajs/inertia";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import { useToggle } from "@vueuse/core";
import Modal from "@/Components/NewModal.vue";
import CategoryTable from "./partials/CategoryTable.vue";
import AddCategory from "./partials/AddCategory.vue";
import PageHeader from "@/Components/PageHeader.vue";
import { useNavigation } from "@/Composables/navigation";
import { useSearch } from "@/Composables/search";
import Pagination from "@/Components/Pagination.vue";

const navigation = computed(() => useNavigation(props.categories));

let openAddDialog = ref(false);

let toggle = useToggle(openAddDialog);

let props = defineProps({
	categories: { type: Object, required: true },
	filters: { type: Object, default: () => {} },
});

let BreadCrumpLinks = [
	{
		name: "Ranks/Categories",
	},
];

let search = ref(props.filters.search);
const searchCategories = (value) => {
	useSearch(value, route("job-category.index"));
};

let openCategory = (categoryId) => {
	Inertia.visit(route("job-category.show", { jobCategory: categoryId }));
};
</script>

<template>
	<MainLayout>
		<Head title="Harmonized Categories" />
		<main class="max-w-7xl mx-auto sm:px-6 lg:px-8">
			<BreadCrumpVue :links="BreadCrumpLinks" />
			<div
				class="overflow-hidden shadow-sm sm:rounded-lg px-6 border-b border-gray-200"
			>
				<PageHeader
					title="Ranks/Grade Categories"
					:total="categories.total"
					:search="search"
					action-text="Add Rank"
					@action-clicked="toggle()"
					@search-entered="(value) => searchCategories(value)"
				/>

				<CategoryTable
					:categories="categories.data"
					@open-category="(categoryId) => openCategory(categoryId)"
				>
					<template #pagination>
						<Pagination :navigation="navigation" />
					</template>
				</CategoryTable>
			</div>
		</main>
		<Modal :show="openAddDialog" @close="toggle()">
			<AddCategory @form-submitted="toggle()" />
		</Modal>
	</MainLayout>
</template>
