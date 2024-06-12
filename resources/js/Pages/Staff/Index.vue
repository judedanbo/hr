<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head } from "@inertiajs/inertia-vue3";
import { ref, computed } from "vue";
import { Inertia } from "@inertiajs/inertia";
import Pagination from "../../Components/Pagination.vue";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import Modal from "@/Components/NewModal.vue";
import AddStaffForm from "./AddStaffForm.vue";
import { useToggle } from "@vueuse/core";
import TableHeader from "./partials/TableHeader.vue";
import StaffList from "./partials/StaffList.vue";
import { useNavigation } from "@/Composables/navigation";
import { useSearch } from "@/Composables/search";
import { Link } from "@inertiajs/inertia-vue3";
import { ArrowDownTrayIcon } from "@heroicons/vue/24/outline";

const navigation = computed(() => useNavigation(props.staff));

let props = defineProps({
	staff: { type: Object, required: true },
	filters: { type: Object, default: () => {} },
});

let openDialog = ref(false);

let toggle = useToggle(openDialog);

const searchStaff = (value) => {
	useSearch(value, route("staff.index"));
};

let openStaff = (staff) => {
	Inertia.visit(route("staff.show", { staff: staff }));
};

let BreadCrumpLinks = [
	{
		name: "Staff",
		url: "",
	},
];
</script>

<template>
	<MainLayout>
		<Head title="Staff" />
		<main class="max-w-7xl mx-auto sm:px-6 lg:px-8">
			<BreadCrumpVue :links="BreadCrumpLinks" />
			<div
				class="overflow-hidden shadow-sm sm:rounded-lg px-6 border-b border-gray-200"
			>
				<TableHeader
					title="Staff"
					:total="staff.total"
					:search="filters.search"
					class="w-4/6"
					action-text="Onboard Staff"
					@action-clicked="toggle()"
					@search-entered="(value) => searchStaff(value)"
				/>

				<div class="flex gap-x-5">
					<a
						class="rounded-md flex gap-x-3 bg-green-600 dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600"
						:href="route('report.staff')"
					>
						<arrow-down-tray-icon class="-ml-1.5 h-5 w-5" aria-hidden="true" />
						Staff position
					</a>
					<a
						class="rounded-md flex gap-x-3 bg-green-600 dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600"
						:href="route('report.staff-details')"
					>
						<arrow-down-tray-icon class="-ml-1.5 h-5 w-5" aria-hidden="true" />
						Staff details
					</a>
					<a
						class="rounded-md flex gap-x-3 bg-green-600 dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600"
						:href="route('report.staff-retirement')"
					>
						<arrow-down-tray-icon class="-ml-1.5 h-5 w-5" aria-hidden="true" />
						Staff retirement
					</a>
				</div>

				<StaffList
					:staff="staff.data"
					@open-staff="(staffId) => openStaff(staffId)"
				>
					<template #pagination>
						<Pagination :navigation="navigation" />
					</template>
				</StaffList>
			</div>
		</main>
		<Modal :show="openDialog" @close="toggle()">
			<AddStaffForm @form-submitted="toggle()" />
		</Modal>
	</MainLayout>
</template>
