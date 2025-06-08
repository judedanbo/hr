<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, usePage } from "@inertiajs/inertia-vue3";
import { ref, computed } from "vue";
import { Inertia } from "@inertiajs/inertia";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import { useToggle } from "@vueuse/core";
import OfficeTable from "./partials/OfficeTable.vue";
import PageHeader from "@/Components/PageHeader.vue";
import { useNavigation } from "@/Composables/navigation";
import { useSearch } from "@/Composables/search";
import Pagination from "@/Components/Pagination.vue";
import { ArrowDownTrayIcon } from "@heroicons/vue/24/outline";
import NoPermission from "@/Components/NoPermission.vue";

let openAddDialog = ref(false);

const page = usePage();
const permissions = computed(() => page.props.value.auth.permissions);

let toggle = useToggle(openAddDialog);

let props = defineProps({
	offices: { type: Object, required: true },
	filters: { type: Object, default: () => {} },
});

let BreadCrumpLinks = [
	{
		name: "Offices",
	},
];

let search = ref(props.filters.search);
const searchOffices = (value) => {
	useSearch(value, route("office.index"));
};

let openOffice = (office) => {
	Inertia.visit(route("office.show", { office: office }));
};
const navigation = computed(() => useNavigation(props.offices));
</script>

<template>
	<MainLayout>
		<!-- {{ offices.data[0].institution_id }} -->
		<Head title="Offices" />
		<main class="max-w-7xl mx-auto sm:px-6 lg:px-8">
			<BreadCrumpVue :links="BreadCrumpLinks" />
			<div
				class="overflow-hidden shadow-sm sm:rounded-lg px-6 border-b border-gray-200"
			>
				<PageHeader
					title="Offices"
					:subtitle="`Total Offices: ${offices.total}`"
					:total="offices.total"
					:search="search"
					@action-clicked="toggle()"
					@search-entered="(value) => searchOffices(value)"
				/>
				<div
					v-if="permissions.includes('download office summary')"
					class="flex gap-x-5"
				>
					<a
						class="rounded-md flex gap-x-3 bg-green-600 dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600"
						:href="route('office.summary')"
					>
						<arrow-down-tray-icon class="-ml-1.5 h-5 w-5" aria-hidden="true" />
						Download Office Summary
					</a>
				</div>

				<OfficeTable
					:offices="offices.data"
					@open-office="(office) => openOffice(office)"
				>
					<template #pagination>
						<Pagination :navigation="navigation" />
					</template>
				</OfficeTable>
			</div>
		</main>
		<!-- <NoPermission v-else /> -->
	</MainLayout>
</template>
