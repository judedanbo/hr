<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head } from "@inertiajs/inertia-vue3";
import { ref, computed } from "vue";
import { Inertia } from "@inertiajs/inertia";
import Pagination from "../../Components/Pagination.vue";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import Modal from "@/Components/NewModal.vue";
// import AddRoleForm from "./partials/AddRoleForm.vue";
import { useToggle } from "@vueuse/core";
import TableHeader from "@/Components/TableHeader.vue";
import RoleList from "./partials/RoleList.vue";
import { useNavigation } from "@/Composables/navigation";
import { useSearch } from "@/Composables/search";
import { Link } from "@inertiajs/inertia-vue3";
import { ArrowDownTrayIcon } from "@heroicons/vue/24/outline";
import AddRoleForm from "./partials/AddRoleForm.vue";

const navigation = computed(() => useNavigation(props.roles));

let props = defineProps({
	roles: { type: Object, required: true },
	filters: { type: Object, default: () => {} },
});

let openDialog = ref(false);

let toggle = useToggle(openDialog);

const searchRole = (value) => {
	useSearch(value, route("role.index"));
};

let openRole = (role) => {
	Inertia.visit(route("role.show", { role: role }));
};

let BreadCrumpLinks = [
	{
		name: "Roles",
		url: "",
	},
];
</script>

<template>
	<MainLayout>
		<Head title="Roles" />
		<main class="max-w-7xl mx-auto sm:px-6 lg:px-8">
			<BreadCrumpVue :links="BreadCrumpLinks" />
			<div
				class="overflow-hidden shadow-sm sm:rounded-lg px-6 border-b border-gray-200"
			>
				<TableHeader
					title="Roles"
					:total="roles.total"
					:search="filters.search"
					class="w-4/6"
					action-text="Crate Role"
					@action-clicked="toggle()"
					@search-entered="(value) => searchRole(value)"
				/>

				<RoleList :roles="roles.data" @open-role="(roleId) => openRole(roleId)">
					<template #pagination>
						<Pagination :navigation="navigation" />
					</template>
				</RoleList>
			</div>
		</main>
		<Modal :show="openDialog" @close="toggle()">
			<AddRoleForm @form-submitted="toggle()" @submit="toggle()" />
		</Modal>
	</MainLayout>
</template>
