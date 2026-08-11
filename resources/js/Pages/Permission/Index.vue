<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, router } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import Pagination from "@/Components/Pagination.vue";
import PageShell from "@/Components/UI/PageShell.vue";
import PageHeader from "@/Components/UI/PageHeader.vue";
import ListToolbar from "@/Components/UI/ListToolbar.vue";
import Modal from "@/Components/NewModal.vue";
import { useToggle } from "@vueuse/core";
import PermissionList from "./partials/PermissionList.vue";
import { useNavigation } from "@/Composables/navigation";
import { useSearch } from "@/Composables/search";
import AddPermissionForm from "./partials/AddPermissionForm.vue";

const props = defineProps({
	permissions: { type: Object, required: true },
	filters: { type: Object, default: () => ({}) },
});

const navigation = computed(() => useNavigation(props.permissions));

const openDialog = ref(false);
const toggle = useToggle(openDialog);

const searchPermission = (value) => {
	useSearch(value, route("permission.index"));
};

const openPermission = (permission) => {
	router.visit(route("permission.show", { permission: permission }));
};

const breadcrumbLinks = [{ name: "Permissions", url: null }];
</script>

<template>
	<MainLayout>
		<Head title="Permissions" />
		<PageShell>
			<PageHeader
				title="Permissions"
				description="Individual abilities that roles and users can be granted."
				:breadcrumbs="breadcrumbLinks"
				:count="permissions.total"
			/>

			<ListToolbar
				title="Permissions"
				action-text="Create Permission"
				:search="filters.search"
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
		</PageShell>

		<Modal :show="openDialog" @close="toggle()">
			<AddPermissionForm @form-submitted="toggle()" @submit="toggle()" />
		</Modal>
	</MainLayout>
</template>
