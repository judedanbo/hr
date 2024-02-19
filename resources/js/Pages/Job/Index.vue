<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head } from "@inertiajs/inertia-vue3";
import { ref, computed } from "vue";
import Pagination from "../../Components/Pagination.vue";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import { useToggle } from "@vueuse/core";
import Modal from "@/Components/NewModal.vue";
import AddRank from "./partials/Add.vue";
import PageHeader from "@/Components/PageHeader.vue";
import { useNavigation } from "@/Composables/navigation";
import { useSearch } from "@/Composables/search";
import JobsList from "./partials/JobsList.vue";
import { Inertia } from "@inertiajs/inertia";

const navigation = computed(() => useNavigation(props.jobs));

let openAddDialog = ref(false);

let toggle = useToggle(openAddDialog);

let props = defineProps({
	jobs: { type: Object, required: true },
	filters: { type: Object, default: () => {} },
});

let BreadCrumpLinks = [
	{
		name: "Ranks",
	},
];
let openJob = (job) => {
	Inertia.visit(route("job.show", { job: job }));
};

let search = ref(props.filters.search);
const searchJobs = (value) => {
	useSearch(value, route("job.index"));
};
</script>

<template>
	<MainLayout>
		<Head title="Departments" />
		<main class="max-w-7xl mx-auto sm:px-6 lg:px-8">
			<BreadCrumpVue :links="BreadCrumpLinks" />
			<div
				class="overflow-hidden shadow-sm sm:rounded-lg px-6 border-b border-gray-200"
			>
				<PageHeader
					title="Ranks"
					:total="jobs.total"
					:search="search"
					action-text="Add Rank"
					@action-clicked="toggle()"
					@search-entered="(value) => searchJobs(value)"
				/>
				<JobsList :jobs="jobs.data" @open-job="(jobId) => openJob(jobId)">
					<template #pagination>
						<Pagination :navigation="navigation" />
					</template>
				</JobsList>
				<!-- {{ navigation }} -->
			</div>
		</main>
		<Modal @close="toggle()" :show="openAddDialog">
			<AddRank @formSubmitted="toggle()" />
		</Modal>
	</MainLayout>
</template>
