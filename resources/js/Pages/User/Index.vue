<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import Pagination from "@/Components/Pagination.vue";
import PageShell from "@/Components/UI/PageShell.vue";
import PageHeader from "@/Components/UI/PageHeader.vue";
import ListToolbar from "@/Components/UI/ListToolbar.vue";
import Modal from "@/Components/NewModal.vue";
import AddUserForm from "./partials/AddUserForm.vue";
import { useToggle } from "@vueuse/core";
import UserList from "./partials/UserList.vue";
import { useNavigation } from "@/Composables/navigation";
import { useSearch } from "@/Composables/search";
import EditUserForm from "./partials/EditUserForm.vue";
import Delete from "./partials/Delete.vue";
import AssociateStaff from "./partials/AssociateStaff.vue";

const navigation = computed(() => useNavigation(props.users));

let props = defineProps({
	users: { type: Object, required: true },
	filters: { type: Object, default: () => {} },
});

const resetPassword = (user) => {
	router.post(route("user.reset-password", { user: user }));
};

const openDeleteModal = ref(false);

const toggleDeleteModal = useToggle(openDeleteModal);

const deleteUser = (user) => {
	selectedUser.value = user;
	toggleDeleteModal();
};

let openDialog = ref(false);

let toggle = useToggle(openDialog);

const openEditDialog = ref(false);

const toggleEditDialog = useToggle(openEditDialog);

const selectedUser = ref(null);
const editUser = (user) => {
	selectedUser.value = user;
	toggleEditDialog();
};

const deleteConfirmed = () => {
	router.delete(route("user.delete", { user: selectedUser.value.id }), {
		onSuccess: () => {
			toggleDeleteModal();
		},
	});
};
const searchUser = (value) => {
	useSearch(value, route("user.index"));
};

let openUser = (user) => {
	router.visit(route("user.show", { user: user }));
};

const breadcrumbLinks = [{ name: "Users", url: null }];
const page = usePage();
const permissions = computed(() => {
	return page.props?.auth.permissions;
});

const associateUserId = ref(null);
const openAssociateModal = ref(false);
const openAssociate = (id) => {
	associateUserId.value = id;
	openAssociateModal.value = true;
};
</script>

<template>
	<MainLayout>
		<Head title="Users" />
		<PageShell>
			<PageHeader
				title="Users"
				description="Accounts that can sign in, and what they are allowed to do."
				:breadcrumbs="breadcrumbLinks"
				:count="users.total"
			/>

			<ListToolbar
				title="Users"
				action-text="Create User"
				:search="filters.search"
				@action-clicked="toggle()"
				@search-entered="(value) => searchUser(value)"
			/>

			<UserList
				:users="users.data"
				:can-associate-staff="permissions?.includes('associate user staff')"
				@open-user="(userId) => openUser(userId)"
				@edit-user="(user) => editUser(user)"
				@delete-user="(user) => deleteUser(user)"
				@reset-password="(user) => resetPassword(user)"
				@associate-staff="(id) => openAssociate(id)"
			>
				<template #pagination>
					<Pagination :navigation="navigation" />
				</template>
			</UserList>
		</PageShell>
		<Modal :show="openDialog" @close="toggle()">
			<AddUserForm @form-submitted="toggle()" />
		</Modal>
		<Modal :show="openEditDialog" @close="toggleEditDialog()">
			<EditUserForm :user="selectedUser" @form-submitted="toggleEditDialog()" />
		</Modal>
		<Delete
			:show="openDeleteModal"
			:model="selectedUser"
			@close="toggleDeleteModal"
			@delete-confirmed="deleteConfirmed()"
		/>
		<Modal
			v-if="associateUserId"
			:show="openAssociateModal"
			@close="
				openAssociateModal = false;
				associateUserId = null;
			"
		>
			<AssociateStaff
				:user="associateUserId"
				@form-submitted="openAssociateModal = false"
			/>
		</Modal>
	</MainLayout>
</template>
