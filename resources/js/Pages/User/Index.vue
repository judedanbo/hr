<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import Pagination from "../../Components/Pagination.vue";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import Modal from "@/Components/NewModal.vue";
import AddUserForm from "./partials/AddUserForm.vue";
import { useToggle } from "@vueuse/core";
import TableHeader from "@/Components/TableHeader.vue";
import UserList from "./partials/UserList.vue";
import { useNavigation } from "@/Composables/navigation";
import { useSearch } from "@/Composables/search";
import { ArrowDownTrayIcon } from "@heroicons/vue/24/outline";
import EditUserForm from "./partials/EditUserForm.vue";
import Delete from "./partials/Delete.vue";

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

let BreadCrumpLinks = [
	{
		name: "Users",
		url: "",
	},
];
const page = usePage();
const permissions = computed(() => {
	return page.props?.auth.permissions;
});
</script>

<template>
	<MainLayout>
		<Head title="Users" />
		<main class="max-w-7xl mx-auto sm:px-6 lg:px-8">
			<BreadCrumpVue :links="BreadCrumpLinks" />
			<div
				class="overflow-hidden shadow-sm sm:rounded-lg px-6 border-b border-gray-200"
			>
				<TableHeader
					title="Users"
					:total="users.total"
					:search="filters.search"
					class="w-4/6"
					action-text="Create User"
					@action-clicked="toggle()"
					@search-entered="(value) => searchUser(value)"
				/>

				<UserList
					:users="users.data"
					@open-user="(userId) => openUser(userId)"
					@edit-user="(user) => editUser(user)"
					@delete-user="(user) => deleteUser(user)"
					@reset-password="(user) => resetPassword(user)"
				>
					<template #pagination>
						<Pagination :navigation="navigation" />
					</template>
				</UserList>
			</div>
		</main>
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
	</MainLayout>
</template>
