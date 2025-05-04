<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, usePage } from "@inertiajs/inertia-vue3";
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
import { ArrowDownTrayIcon } from "@heroicons/vue/24/outline";
import NoPermission from "@/Components/NoPermission.vue";

let openAddDialog = ref(false);

const page = usePage();
const permissions = computed(() => page.props.value.auth.permissions);

let toggle = useToggle(openAddDialog);

let props = defineProps({
	jobs: { type: Object, required: true },
	filters: { type: Object, default: () => {} },
});
const navigation = computed(() => useNavigation(props.jobs));

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
		<main
			v-if="permissions.includes('view all jobs')"
			class="max-w-7xl mx-auto sm:px-6 lg:px-8"
		>
			<BreadCrumpVue :links="BreadCrumpLinks" />
			<div class="overflow-hidden shadow-sm sm:rounded-lg px-6">
				<PageHeader
					title="Ranks"
					:total="jobs.total"
					:search="search"
					:add-permission="permissions.includes('create job')"
					action-text="Add Rank"
					@action-clicked="toggle()"
					@search-entered="(value) => searchJobs(value)"
				/>
				<div
					v-if="
						permissions.includes('download active staff data') ||
						permissions.includes('download separated staff data')
					"
					class="flex gap-x-5"
				>
					<a
						v-if="permissions.includes('download job summary')"
						class="rounded-md flex gap-x-3 bg-green-600 dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600"
						:href="route('job.summary')"
					>
						<arrow-down-tray-icon class="-ml-1.5 h-5 w-5" aria-hidden="true" />
						Download summary
					</a>
				</div>
				<JobsList :jobs="jobs.data" @open-job="(jobId) => openJob(jobId)">
					<template #pagination>
						<Pagination :navigation="navigation" />
					</template>
				</JobsList>
				<!-- {{ navigation }} -->
			</div>
		</main>
		<NoPermission v-else />
		<Modal @close="toggle()" :show="openAddDialog">
			<AddRank @formSubmitted="toggle()" />
		</Modal>
	</MainLayout>
</template>
