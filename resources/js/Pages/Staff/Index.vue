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
		<div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
			<BreadCrumpVue :links="BreadCrumpLinks" />
			<div
				class="overflow-hidden shadow-sm sm:rounded-lg px-6 border-b border-gray-200"
			>
				<TableHeader
					title="Staff"
					:total="staff.total"
					:search="filters.search"
					action-text="Onboard Staff"
					@action-clicked="toggle()"
					@search-entered="(value) => searchStaff(value)"
				/>

				<StaffList
					:staff="staff.data"
					@open-staff="(staffId) => openStaff(staffId)"
				>
					<template #pagination>
						<Pagination :navigation="navigation" />
					</template>
				</StaffList>
			</div>
		</div>
		<Modal :show="openDialog" @close="toggle()">
			<AddStaffForm @form-submitted="toggle()" />
		</Modal>
	</MainLayout>
</template>
