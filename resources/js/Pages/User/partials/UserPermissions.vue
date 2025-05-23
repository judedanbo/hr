<script setup>
import { Inertia } from "@inertiajs/inertia";
import { ref, watch, computed } from "vue";
import { useToggle } from "@vueuse/core";
import Modal from "@/Components/NewModal.vue";
import AddUserPermission from "./AddUserPermission.vue";
import Edit from "./Edit.vue";
import Delete from "./Delete.vue";
import PermissionsList from "./PermissionsList.vue";

const emit = defineEmits(["closeForm"]);

let props = defineProps({
	user: {
		type: Number,
		required: true,
	},
	permissions: {
		type: Array,
		default: () => null,
	},
	canAdd: {
		type: Boolean,
		default: false,
	},
});

const userPermissions = computed(() => {
	return props.permissions.map((permission) => {
		return permission.name;
	});
});

const openAddPermissionModal = ref(false);
let toggleAddPermissionModal = useToggle(openAddPermissionModal);
// emit("closeForm");

const openEditPromoteModal = ref(false);
const toggleEditPermissionModal = useToggle(openEditPromoteModal);
const editModel = ref(null);

const openDeletePermissionModal = ref(false);
const toggleDeletePermissionModal = useToggle(openDeletePermissionModal);

const deleteModel = ref(null);
const confirmDeletePermission = (model) => {
	deleteModel.value = model;
	toggleDeletePermissionModal();
	// deletePermission(model.staff_id, model.rank_id);
};

const deletePermission = (user, permission) => {
	Inertia.patch(route("user.revoke.permissions", { user: user }), {
		permission,
		preserveScroll: true,
	});
	toggleDeletePermissionModal();
};

// watch(
// 	() => props.showPermissionForm,
// 	(value) => {
// 		if (value) {
// 			openAddPermissionModal.value = true;
// 		}
// 	},
// );
</script>
<template>
	<!-- Permission History -->
	<main>
		<h2 class="sr-only">User Permissions</h2>
		<div
			class="rounded-lg bg-green-200 dark:bg-gray-800 shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-600/80 max-h-80"
		>
			<dl class="flex flex-wrap">
				<div class="flex-auto pl-6 pt-6">
					<dt
						class="text-md tracking-wide font-semibold leading-6 text-green-900 dark:text-gray-100"
					>
						User Permissions
					</dt>
				</div>
				<div class="flex-none self-end px-6 pt-4">
					<button
						v-if="canAdd"
						class="rounded-md bg-green-50 dark:bg-gray-400 px-2 py-1 text-xs font-medium text-green-600 dark:text-gray-50 ring-1 ring-inset ring-green-600/20 dark:ring-gray-500"
						@click="toggleAddPermissionModal()"
					>
						{{ "Add Permission" }}
					</button>
				</div>
				<PermissionsList
					:permissions="permissions"
					class="w-full max-h-64 overflow-y-scroll"
					@delete-permission="(model) => confirmDeletePermission(model)"
				/>
			</dl>
		</div>

		<Modal :show="openAddPermissionModal" @close="toggleAddPermissionModal()">
			<AddUserPermission
				:user="user"
				:user-permissions="userPermissions"
				@form-submitted="toggleAddPermissionModal()"
			/>
		</Modal>
		<Modal :show="openEditPromoteModal" @close="toggleEditPermissionModal()">
			<Edit
				:model="editModel"
				:staff="staff"
				:institution="institution"
				@form-submitted="toggleEditPermissionModal()"
			/>
		</Modal>

		<Delete
			:open="openDeletePermissionModal"
			:model="deleteModel"
			@delete-confirmed="deletePermission(user, deleteModel.name)"
			@close="toggleDeletePermissionModal()"
		/>
	</main>
</template>
