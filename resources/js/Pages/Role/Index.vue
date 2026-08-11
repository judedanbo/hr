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
import RoleList from "./partials/RoleList.vue";
import { useNavigation } from "@/Composables/navigation";
import { useSearch } from "@/Composables/search";
import AddRoleForm from "./partials/AddRoleForm.vue";

const props = defineProps({
	roles: { type: Object, required: true },
	filters: { type: Object, default: () => ({}) },
});

const navigation = computed(() => useNavigation(props.roles));

const openDialog = ref(false);
const toggle = useToggle(openDialog);

const searchRole = (value) => {
	useSearch(value, route("role.index"));
};

const openRole = (role) => {
	router.visit(route("role.show", { role: role }));
};

const breadcrumbLinks = [{ name: "Roles", url: null }];
</script>

<template>
	<MainLayout>
		<Head title="Roles" />
		<PageShell>
			<PageHeader
				title="Roles"
				description="Group permissions and assign them to users."
				:breadcrumbs="breadcrumbLinks"
				:count="roles.total"
			/>

			<ListToolbar
				title="Roles"
				action-text="Create Role"
				:search="filters.search"
				@action-clicked="toggle()"
				@search-entered="(value) => searchRole(value)"
			/>

			<RoleList :roles="roles.data" @open-role="(roleId) => openRole(roleId)">
				<template #pagination>
					<Pagination :navigation="navigation" />
				</template>
			</RoleList>
		</PageShell>

		<Modal :show="openDialog" @close="toggle()">
			<AddRoleForm @form-submitted="toggle()" @submit="toggle()" />
		</Modal>
	</MainLayout>
</template>
