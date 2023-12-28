<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head } from "@inertiajs/inertia-vue3";
import { ref } from "vue";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import { useToggle } from "@vueuse/core";
import Modal from "@/Components/NewModal.vue";
import JobCategory from "./partials/JobCategory.vue";
import AddJobsToCategory from "./partials/AddJobsToCategory.vue";

let openAddDialog = ref(false);

let toggle = useToggle(openAddDialog);

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
			<div class="overflow-hidden shadow-sm sm:rounded-lg">
				<div class="p-2 border-b border-gray-200">
					<div
						class="grid grid-cols-1 gap-6 mt-2 md:grid-cols-2 lg:grid-cols-4"
					></div>
					<BreadCrumpVue :links="BreadCrumpLinks" />
					<h2 class="text-3xl text-gray-900 dark:text-gray-50 mt-4">
						Ranks/Grades Categories
					</h2>

					<JobCategory :category="category" @add-rank="toggle()" />
				</div>
			</div>
		</div>
		<Modal :show="openAddDialog" @close="toggle()">
			<AddJobsToCategory @form-submitted="toggle()" />
		</Modal>
	</MainLayout>
</template>
