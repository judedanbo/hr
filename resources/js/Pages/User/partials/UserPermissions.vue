<script setup>
import { router } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import { useToggle } from "@vueuse/core";
import Modal from "@/Components/NewModal.vue";
import AddUserPermission from "./AddUserPermission.vue";
import Delete from "./Delete.vue";
import PermissionsList from "./PermissionsList.vue";

defineEmits(["closeForm"]);

const props = defineProps({
	user: { type: Number, required: true },
	directPermissions: { type: Array, default: () => [] },
	inheritedPermissions: { type: Array, default: () => [] },
	canAdd: { type: Boolean, default: false },
});

const directNames = computed(() =>
	props.directPermissions.map((permission) => permission.name),
);

const openAddPermissionModal = ref(false);
const toggleAddPermissionModal = useToggle(openAddPermissionModal);

const openDeletePermissionModal = ref(false);
const toggleDeletePermissionModal = useToggle(openDeletePermissionModal);
const deleteModel = ref(null);

const confirmDeletePermission = (model) => {
	deleteModel.value = model;
	toggleDeletePermissionModal();
};

const deletePermission = (user, permission) => {
	router.patch(route("user.revoke.permissions", { user }), {
		permission,
		preserveScroll: true,
	});
	toggleDeletePermissionModal();
};
</script>

<template>
	<main>
		<h2 class="sr-only">User Permissions</h2>
		<div
			class="rounded-2xl border border-green-200/60 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm"
		>
			<div class="flex items-center justify-between px-5 pt-5 pb-3">
				<h3 class="text-sm font-semibold text-green-900 dark:text-gray-100">
					Permissions
				</h3>
				<button
					v-if="canAdd"
					class="inline-flex items-center gap-1 rounded-md bg-green-600 px-2.5 py-1 text-xs font-medium text-white hover:bg-green-500 dark:bg-gray-600 dark:hover:bg-gray-500"
					@click="toggleAddPermissionModal()"
				>
					Add Permission
				</button>
			</div>

			<div class="px-5 pb-5 space-y-5">
				<div>
					<p
						class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"
					>
						Direct
					</p>
					<PermissionsList
						:permissions="directPermissions"
						@delete-permission="(model) => confirmDeletePermission(model)"
					/>
				</div>

				<div v-if="inheritedPermissions.length > 0">
					<p
						class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"
					>
						Inherited from roles
					</p>
					<ul class="flex flex-wrap gap-2">
						<li
							v-for="permission in inheritedPermissions"
							:key="permission.id"
							class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 dark:bg-gray-700 px-3 py-1 text-sm text-gray-600 dark:text-gray-300 ring-1 ring-inset ring-gray-400/20"
						>
							{{ permission.name }}
							<span class="text-xs text-gray-400 dark:text-gray-400"
								>via {{ permission.via }}</span
							>
						</li>
					</ul>
				</div>
			</div>
		</div>

		<Modal :show="openAddPermissionModal" @close="toggleAddPermissionModal()">
			<AddUserPermission
				:user="user"
				:user-permissions="directNames"
				@form-submitted="toggleAddPermissionModal()"
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
