<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head } from "@inertiajs/vue3";
import Pagination from "../../Components/Pagination.vue";
import { useNavigation } from "@/Composables/navigation";
import { useToggle } from "@vueuse/core";
import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";
import NewModal from "@/Components/NewModal.vue";
import PermissionRoles from "./partials/PermissionRoles.vue";
import PermissionUsers from "./partials/PermissionUsers.vue";
import { PlusIcon, PencilIcon, TrashIcon } from "@heroicons/vue/20/solid";
import EditPermissionForm from "./partials/EditPermissionForm.vue";
import PageShell from "@/Components/UI/PageShell.vue";
import PageHeader from "@/Components/UI/PageHeader.vue";

const roleNavigation = computed(() => useNavigation(props.roles));
const userNavigation = computed(() => useNavigation(props.users));
let showEditPermissionForm = ref(false);
let showDeleteConfirmation = ref(false);

let toggleEditPermissionForm = useToggle(showEditPermissionForm);
let toggleDeleteConfirmation = useToggle(showDeleteConfirmation);

let props = defineProps({
	permission: { type: Object, default: () => null },
	roles: { type: Object, default: () => null },
	users: { type: Object, default: () => null },
});

const breadcrumbLinks = [
	{
		name: "Permissions",
		url: "/permission",
	},
	{
		name: props.permission.display_name,
		url: "/permission/" + props.permission.id,
	},
];

const deletePermission = () => {
	router.delete(
		route("permission.destroy", { permission: props.permission.id }),
		{
			preserveScroll: true,
			onSuccess: () => {
				router.visit(route("permission.index"));
			},
		},
	);
};
</script>
<template>
	<Head :title="permission.name" />

	<MainLayout>
		<PageShell>
			<PageHeader title="Permission" :breadcrumbs="breadcrumbLinks">
				<template #meta>
					<p
						class="mt-1 text-2xl font-semibold tracking-tight text-gray-900 dark:text-gray-50"
					>
						{{ permission.display_name }}
					</p>
				</template>
				<template #actions>
					<button
						type="button"
						class="btn btn-secondary"
						@click="toggleEditPermissionForm()"
					>
						<PencilIcon class="-ml-0.5 h-5 w-5" aria-hidden="true" />
						Edit
					</button>
					<button
						type="button"
						class="btn btn-danger"
						@click="toggleDeleteConfirmation()"
					>
						<TrashIcon class="-ml-0.5 h-5 w-5" aria-hidden="true" />
						Delete
					</button>
				</template>
			</PageHeader>

			<PermissionRoles :roles="roles">
				<template #pagination>
					<Pagination :navigation="roleNavigation" />
				</template>
			</PermissionRoles>

			<PermissionUsers :users="users">
				<template #pagination>
					<Pagination :navigation="userNavigation" />
				</template>
			</PermissionUsers>
		</PageShell>

		<NewModal
			:show="showEditPermissionForm"
			title="Edit Permission"
			subtitle="Edit permission details"
			@close="toggleEditPermissionForm()"
		>
			<EditPermissionForm
				:permission="permission"
				@form-submitted="toggleEditPermissionForm()"
			/>
		</NewModal>
		<NewModal
			:show="showDeleteConfirmation"
			title="Delete Permission"
			subtitle="Are you sure you want to delete this permission?"
			@close="toggleDeleteConfirmation()"
		>
			<div class="p-4">
				<p class="text-sm text-gray-600 dark:text-gray-300 mb-4">
					This action will remove the permission "{{ permission.display_name }}"
					from all roles and users. This cannot be undone.
				</p>
				<div class="flex gap-2">
					<button
						type="button"
						class="inline-flex items-center px-4 py-2 bg-red-600 text-white font-semibold rounded-md shadow-sm hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
						@click="deletePermission()"
					>
						Delete
					</button>
					<button
						type="button"
						class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-700 font-semibold rounded-md shadow-sm hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500"
						@click="toggleDeleteConfirmation()"
					>
						Cancel
					</button>
				</div>
			</div>
		</NewModal>
	</MainLayout>
</template>
