<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head } from "@inertiajs/vue3";
import Pagination from "@/Components/Pagination.vue";
import { useNavigation } from "@/Composables/navigation";

import { useToggle } from "@vueuse/core";
import { ref, computed } from "vue";
import NewModal from "@/Components/NewModal.vue";
import RolePermissions from "./partials/RolePermissions.vue";
import RoleUsers from "./partials/RoleUsers.vue";
import { PlusIcon } from "@heroicons/vue/20/solid";
import AddPermissionForm from "./partials/AddPermissionForm.vue";
import AddRoleUsers from "./partials/AddRoleUsers.vue";
import PageShell from "@/Components/UI/PageShell.vue";
import PageHeader from "@/Components/UI/PageHeader.vue";

const permissionNavigation = computed(() => useNavigation(props.permissions));
const userNavigation = computed(() => useNavigation(props.users));
let showPromotionForm = ref(false);
let showAddPermissionForm = ref(false);
let showAddUsersForm = ref(false);
let openEditModal = ref(false);

let toggle = useToggle(openEditModal);

let togglePermissionsForm = useToggle(showPromotionForm);
let toggleAddPermissionForm = useToggle(showAddPermissionForm);
let toggleAddUsersForm = useToggle(showAddUsersForm);

let props = defineProps({
	role: { type: Object, default: () => null },
	permissions: { type: Object, default: () => null },
	users: { type: Object, default: () => null },
	rolePermissionNames: { type: Array, default: () => [] },
	filters: { type: Object, default: () => ({ permission_search: "" }) },
});

const breadcrumbLinks = [
	{
		name: "Roles",
		url: "/role",
	},
	{
		name: props.role.display_name,
		url: null,
	},
];
</script>
<template>
	<Head :title="role.name" />

	<MainLayout>
		<PageShell>
			<PageHeader title="Role" :breadcrumbs="breadcrumbLinks">
				<template #meta>
					<p
						class="mt-1 text-2xl font-semibold tracking-tight text-gray-900 dark:text-gray-50"
					>
						{{ role.display_name }}
					</p>
				</template>
				<template #actions>
					<button
						type="button"
						class="btn btn-secondary"
						@click="toggleAddUsersForm()"
					>
						Add Users
					</button>
					<button
						type="button"
						class="btn btn-secondary"
						@click="toggleAddPermissionForm()"
					>
						Add Permissions
					</button>
					<button type="button" class="btn btn-primary" @click="toggle()">
						<PlusIcon class="-ml-0.5 h-5 w-5" aria-hidden="true" />
						Edit
					</button>
				</template>
			</PageHeader>

			<RoleUsers :users="users" :role="role.id">
				<template #pagination>
					<Pagination :navigation="userNavigation" />
				</template>
			</RoleUsers>

			<RolePermissions
				:permissions="permissions"
				:role="role.id"
				:initial-search="filters.permission_search"
				@close-form="togglePermissionsForm()"
			>
				<template #pagination>
					<Pagination :navigation="permissionNavigation" />
				</template>
			</RolePermissions>
		</PageShell>

		<NewModal
			:show="showAddPermissionForm"
			title="Edit Role"
			subtitle="Edit role details"
			@close="toggleAddPermissionForm()"
		>
			<AddPermissionForm
				:role="role.id"
				:role-permissions="rolePermissionNames"
				@form-submitted="toggleAddPermissionForm()"
			/>
		</NewModal>
		<NewModal
			:show="showAddUsersForm"
			title="Assign Users to Role"
			subtitle="Select users to assign to this role"
			@close="toggleAddUsersForm()"
		>
			<AddRoleUsers
				:role="role.id"
				@form-submitted="toggleAddUsersForm()"
				@close="toggleAddUsersForm()"
			/>
		</NewModal>
	</MainLayout>
</template>
