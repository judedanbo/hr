<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head } from "@inertiajs/inertia-vue3";
import { ref, computed } from "vue";
import { Inertia } from "@inertiajs/inertia";
import Pagination from "../../Components/Pagination.vue";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import Modal from "@/Components/NewModal.vue";
import AddUserForm from "./partials/AddUserForm.vue";
import { useToggle } from "@vueuse/core";
import TableHeader from "@/Components/TableHeader.vue";
import UserList from "./partials/UserList.vue";
import { useNavigation } from "@/Composables/navigation";
import { useSearch } from "@/Composables/search";
import { Link } from "@inertiajs/inertia-vue3";
import { ArrowDownTrayIcon } from "@heroicons/vue/24/outline";

const navigation = computed(() => useNavigation(props.users));

let props = defineProps({
	users: { type: Object, required: true },
	filters: { type: Object, default: () => {} },
});

let openDialog = ref(false);

let toggle = useToggle(openDialog);

const searchUser = (value) => {
	useSearch(value, route("user.index"));
};

let openUser = (user) => {
	// console.log(user);
	Inertia.visit(route("user.show", { user: user }));
};

let BreadCrumpLinks = [
	{
		name: "Users",
		url: "",
	},
];
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

				<!-- <div class="flex gap-x-5">
					<a
						class="rounded-md flex gap-x-3 bg-green-600 dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600"
						:href="route('report.staff')"
					>
						<arrow-down-tray-icon class="-ml-1.5 h-5 w-5" aria-hidden="true" />
						Staff position
					</a>
					<a
						class="rounded-md flex gap-x-3 bg-green-600 dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600"
						:href="route('report.staff-details')"
					>
						<arrow-down-tray-icon class="-ml-1.5 h-5 w-5" aria-hidden="true" />
						Staff details
					</a>
					<a
						class="rounded-md flex gap-x-3 bg-green-600 dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600"
						:href="route('report.staff-retirement')"
					>
						<arrow-down-tray-icon class="-ml-1.5 h-5 w-5" aria-hidden="true" />
						Staff retirement
					</a>
					<a
						class="rounded-md flex gap-x-3 bg-green-600 dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600"
						:href="route('report.staff-pending-transfer')"
					>
						<arrow-down-tray-icon class="-ml-1.5 h-5 w-5" aria-hidden="true" />
						Pending Transfer
					</a>
				</div> -->

				<UserList :users="users.data" @open-user="(userId) => openUser(userId)">
					<template #pagination>
						<Pagination :navigation="navigation" />
					</template>
				</UserList>
			</div>
		</main>
		<Modal :show="openDialog" @close="toggle()">
			<AddUserForm @form-submitted="toggle()" />
		</Modal>
	</MainLayout>
</template>
