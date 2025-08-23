<script setup>
import { router } from "@inertiajs/vue3";
import { ref, watch } from "vue";
import { useToggle } from "@vueuse/core";
import Modal from "@/Components/NewModal.vue";
import AddUserRole from "./AddUserRole.vue";
import Edit from "./Edit.vue";
import Delete from "./Delete.vue";
import RolesList from "./RolesList.vue";

const emit = defineEmits(["closeForm"]);

let props = defineProps({
	roles: {
		type: Array,
		required: true,
	},
	user: {
		type: Number,
		required: true,
	},
	canAdd: {
		type: Boolean,
		default: false,
	},
});

const openAddRoleModal = ref(false);
let toggleAddRoleModal = useToggle(openAddRoleModal);
// emit("closeForm");

const openEditPromoteModal = ref(false);
const toggleEditRoleModal = useToggle(openEditPromoteModal);
const editModel = ref(null);

const openDeleteRoleModal = ref(false);
const toggleDeleteRoleModal = useToggle(openDeleteRoleModal);

const deleteModel = ref(null);
const confirmDeleteRole = (model) => {
	deleteModel.value = model;
	toggleDeleteRoleModal();
	// deleteRole(model.staff_id, model.rank_id);
};

const deleteRole = (user, role) => {
	router.patch(route("user.revoke.roles", { user: user }), {
		role,
		preserveScroll: true,
	});
	toggleDeleteRoleModal();
};
</script>
<template>
	<!-- Role History -->
	<main>
		<h2 class="sr-only">User Roles</h2>
		<div
			class="rounded-lg bg-green-200 dark:bg-gray-800 shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-600/80 max-h-80"
		>
			<dl class="flex flex-wrap">
				<div class="flex-auto pl-6 pt-6">
					<dt
						class="text-md tracking-wide font-semibold leading-6 text-gray-900 dark:text-gray-100"
					>
						User Roles
					</dt>
				</div>
				<div class="flex-none self-end px-6 pt-4">
					<button
						v-if="canAdd"
						class="rounded-md bg-green-50 dark:bg-gray-400 px-2 py-1 text-xs font-medium text-green-600 dark:text-gray-50 ring-1 ring-inset ring-green-600/20 dark:ring-gray-500"
						@click="toggleAddRoleModal()"
					>
						{{ "Add Role" }}
					</button>
				</div>
				<RolesList
					class="w-full max-h-64 overflow-y-scroll"
					:roles="props.roles"
					@delete-role="(model) => confirmDeleteRole(model)"
				/>
			</dl>
		</div>

		<Modal :show="openAddRoleModal" @close="toggleAddRoleModal()">
			<AddUserRole :user="user" @form-submitted="toggleAddRoleModal()" />
		</Modal>
		<Modal :show="openEditPromoteModal" @close="toggleEditRoleModal()">
			<Edit
				:model="editModel"
				:staff="staff"
				:institution="institution"
				@form-submitted="toggleEditRoleModal()"
			/>
		</Modal>

		<Delete
			:open="openDeleteRoleModal"
			:model="deleteModel"
			@delete-confirmed="deleteRole(user, deleteModel.name)"
			@close="toggleDeleteRoleModal()"
		/>
	</main>
</template>
