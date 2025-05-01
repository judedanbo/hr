<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, usePage } from "@inertiajs/inertia-vue3";
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
import { ArrowDownTrayIcon } from "@heroicons/vue/24/outline";

const navigation = computed(() => useNavigation(props.categories));

let openAddDialog = ref(false);

const page = usePage();
const permissions = computed(() => page.props.value.auth.permissions);

let toggle = useToggle(openAddDialog);

let props = defineProps({
	categories: { type: Object, required: true },
	filters: { type: Object, default: () => {} },
});

let BreadCrumpLinks = [
	{
		name: "Harmonized Grades",
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
		<!-- {{ categories.data[0].institution_id }} -->
		<Head title="Harmonized Categories" />
		<main class="max-w-7xl mx-auto sm:px-6 lg:px-8">
			<BreadCrumpVue :links="BreadCrumpLinks" />
			<div
				class="overflow-hidden shadow-sm sm:rounded-lg px-6 border-b border-gray-200"
			>
				<PageHeader
					title="Harmonized Grades"
					:total="categories.total"
					:search="search"
					action-text="Add Harmonized Grade"
					@action-clicked="toggle()"
					@search-entered="(value) => searchCategories(value)"
				/>
				<div
					v-if="
						permissions.includes('download active staff data') ||
						permissions.includes('download separated staff data')
					"
					class="flex gap-x-5"
				>
					<a
						class="rounded-md flex gap-x-3 bg-green-600 dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600"
						:href="route('job-category.summary')"
					>
						<arrow-down-tray-icon class="-ml-1.5 h-5 w-5" aria-hidden="true" />
						Download summary
					</a>
				</div>

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
			<AddCategory
				:institution="categories.data[0].institution_id"
				@form-submitted="toggle()"
			/>
		</Modal>
	</MainLayout>
</template>
