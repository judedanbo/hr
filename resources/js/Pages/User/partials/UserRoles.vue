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
	hasStaffRecord: {
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
			class="rounded-2xl border border-green-200/60 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm"
		>
			<div class="flex items-center justify-between px-5 pt-5 pb-3">
				<h3 class="text-sm font-semibold text-green-900 dark:text-gray-100">
					Roles
				</h3>
				<button
					v-if="canAdd"
					class="inline-flex items-center gap-1 rounded-md bg-green-600 px-2.5 py-1 text-xs font-medium text-white hover:bg-green-500 dark:bg-gray-600 dark:hover:bg-gray-500"
					@click="toggleAddRoleModal()"
				>
					Add Role
				</button>
			</div>
			<RolesList
				:roles="props.roles"
				@delete-role="(model) => confirmDeleteRole(model)"
			/>
		</div>

		<Modal :show="openAddRoleModal" @close="toggleAddRoleModal()">
			<AddUserRole :user="user" :has-staff-record="props.hasStaffRecord" @form-submitted="toggleAddRoleModal()" />
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
