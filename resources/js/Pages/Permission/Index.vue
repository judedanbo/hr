<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";
import Pagination from "../../Components/Pagination.vue";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import Modal from "@/Components/NewModal.vue";
import { useToggle } from "@vueuse/core";
import TableHeader from "@/Components/TableHeader.vue";
import PermissionList from "./partials/PermissionList.vue";
import { useNavigation } from "@/Composables/navigation";
import { useSearch } from "@/Composables/search";
import AddPermissionForm from "./partials/AddPermissionForm.vue";

const navigation = computed(() => useNavigation(props.permissions));

let props = defineProps({
	permissions: { type: Object, required: true },
	filters: { type: Object, default: () => {} },
});

let openDialog = ref(false);

let toggle = useToggle(openDialog);

const searchPermission = (value) => {
	useSearch(value, route("permission.index"));
};

let openPermission = (permission) => {
	router.visit(route("permission.show", { permission: permission }));
};

let BreadCrumpLinks = [
	{
		name: "Permissions",
		url: "",
	},
];
</script>

<template>
	<MainLayout>
		<Head title="Permissions" />
		<main class="max-w-7xl mx-auto sm:px-6 lg:px-8">
			<BreadCrumpVue :links="BreadCrumpLinks" />
			<div
				class="overflow-hidden shadow-sm sm:rounded-lg px-6 border-b border-gray-200"
			>
				<TableHeader
					title="Permissions"
					:total="permissions.total"
					:search="filters.search"
					class="w-4/6"
					action-text="Create Permission"
					@action-clicked="toggle()"
					@search-entered="(value) => searchPermission(value)"
				/>

				<PermissionList
					:permissions="permissions.data"
					@open-permission="(permissionId) => openPermission(permissionId)"
				>
					<template #pagination>
						<Pagination :navigation="navigation" />
					</template>
				</PermissionList>
			</div>
		</main>
		<Modal :show="openDialog" @close="toggle()">
			<AddPermissionForm @form-submitted="toggle()" @submit="toggle()" />
		</Modal>
	</MainLayout>
</template>
