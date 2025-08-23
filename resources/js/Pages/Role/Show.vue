<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head } from "@inertiajs/vue3";
import Pagination from "../../Components/Pagination.vue";
import { useNavigation } from "@/Composables/navigation";

// import Summary from "@/Pages/Person/Summary.vue";
//import UserRoles from "./partials/UserRoles.vue";
// import UserPermissions from "./partials/UserPermissions.vue";

// import Dependents from "./Dependents.vue";
// import Address from "./Address.vue";
// import Notes from "./Notes.vue";
import { useToggle } from "@vueuse/core";
import { ref, computed } from "vue";
import { Menu, MenuButton, MenuItem, MenuItems } from "@headlessui/vue";
// import Avatar from "../Person/partials/Avatar.vue";
import NewModal from "@/Components/NewModal.vue";
// import EditStaffForm from "./EditStaffForm.vue";
// import EditContactForm from "./EditContactForm.vue";
import RolePermissions from "./partials/RolePermissions.vue";
import RoleUsers from "./partials/RoleUsers.vue";
import { PlusIcon } from "@heroicons/vue/20/solid";
import AddPermissionForm from "./partials/AddPermissionForm.vue";
import BreadCrump from "@/Components/BreadCrump.vue";

const permissionNavigation = computed(() => useNavigation(props.permissions));
const userNavigation = computed(() => useNavigation(props.users));
let showPromotionForm = ref(false);
let showAddPermissionForm = ref(false);
let openEditModal = ref(false);

let toggle = useToggle(openEditModal);

let togglePermissionsForm = useToggle(showPromotionForm);
let toggleAddPermissionForm = useToggle(showAddPermissionForm);

let props = defineProps({
	role: { type: Object, default: () => null },
	permissions: { type: Object, default: () => null },
	users: { type: Object, default: () => null },
});

let breadcrumbLinks = [
	{
		name: "Roles",
		url: "/role",
	},
	{
		name: props.role.display_name,
		url: "/roles/" + props.role.id,
	},
	// { name: props.person.name, url: "/" },
];
const openEditContact = ref(false);
const toggleEditContactModal = useToggle(openEditContact);
// const confirmDelete = useToggle(openEditModal);

const editContactModal = () => {
	openEditContact.value = !openEditContact.value;
};
</script>
<template>
	<Head :title="role.name" />

	<MainLayout>
		<main>
			<BreadCrump :links="breadcrumbLinks" />

			<header
				class="relative isolate pt-4 border dark:border-gray-600 rounded-lg"
			>
				<div class="absolute inset-0 -z-10 overflow-hidden" aria-hidden="true">
					<div
						class="absolute left-16 top-full -mt-16 transform-gpu opacity-50 blur-3xl xl:left-1/2 xl:-ml-80"
					>
						<div
							class="aspect-[1154/678] w-[72.125rem] bg-gradient-to-br from-green-100 dark:from-gray-100 to-yellow-200 dark:to-gray-800 dark:border-gray-700 rounded-3xl"
							style="
								clip-path: polygon(
									100% 38.5%,
									82.6% 100%,
									60.2% 37.7%,
									52.4% 32.1%,
									47.5% 41.8%,
									45.2% 65.6%,
									27.5% 23.4%,
									0.1% 35.3%,
									17.9% 0%,
									27.7% 23.4%,
									76.2% 2.5%,
									74.2% 56%,
									100% 38.5%
								);
							"
						/>
					</div>
					<div class="absolute inset-x-0 bottom-0 h-px bg-gray-900/5" />
				</div>

				<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
					<div
						class="mx-auto flex flex-wrap max-w-2xl items-center md:justify-between lg:justify-start gap-x-8 lg:mx-0 lg:max-w-none"
					>
						<div
							class="flex flex-wrap items-center justify-between md:justify-start gap-x-6 w-full md:w-1/2"
						>
							<!-- <Avatar
								:initials="person.initials"
								:image="person.image"
								size="lg"
							/> -->
							<!-- <img
								v-if="person.image"
								:src="person.image"
								:alt="person.name"
								class="w-24 h-24 object-cover object-center rounded-full"
							/>
							<div
								v-else
								class="flex justify-center items-center h-24 w-24 flex-none rounded-lg ring-1 ring-green-400/60 dark:ring-gray-400 text-5xl text-green-400/50 dark:text-gray-300 font-bold tracking-wide"
							>
								{{ person.initials }}
							</div> -->
							<div class="">
								<!-- <div class="text-sm leading-6 text-gray-500 dark:text-gray-300">
									File Number
									<span class="text-gray-700 dark:text-gray-100">{{
										staff.file_number
									}}</span>
								</div> -->
								<!-- <div class="text-sm leading-6 text-gray-500 dark:text-gray-300">
									Staff Number
									<span class="text-gray-700 dark:text-gray-100">{{
										staff.staff_number
									}}</span>
								</div> -->
								<div
									class="mt-1 text-xl font-semibold leading-6 text-gray-900 dark:text-white"
								>
									{{ role.display_name }}
								</div>
							</div>
						</div>
						<div
							class="flex items-center gap-x-4 sm:gap-x-6 justify-between w-full md:w-fit"
						>
							<!-- <button
								type="button"
								class="hidden text-sm font-semibold leading-6 text-green-900 dark:text-white sm:block"
								@click="togglePermissionsForm()"
							>
								Add Roles
							</button> -->
							<button
								type="button"
								class="hidden text-sm font-semibold leading-6 text-green-900 dark:text-white sm:block"
								@click="toggleAddPermissionForm()"
							>
								Add Permissions
							</button>

							<a
								href="#"
								class="ml-auto flex items-center gap-x-1 rounded-md bg-green-600 dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600"
								@click.prevent="toggle()"
							>
								<PlusIcon class="-ml-1.5 h-5 w-5" aria-hidden="true" />
								Edit
							</a>
						</div>
					</div>
				</div>
			</header>

			<div class="mx-auto max-w-7xl py-4">
				<div
					class="mx-auto lg:grid max-w-2xl grid-cols-1 grid-rows-1 items-start lg:mx-0 px-4 lg:max-w-none lg:grid-cols-3 gap-4"
				>
					<div class="md:col-start-3 flex flex-wrap gap-4 w-full">
						<!-- Employment summary -->
						<!-- <Summary :person="role" @open-edit-person="toggle()" /> -->
					</div>

					<div
						class="col-start-1 col-span-3 lg:col-span-2 lg:row-span-2 lg:row-end-2 space-y-4"
					>
						<RoleUsers
							:users="users"
							:role="role.id"
							class="w-full xl:flex-1"
							@close-form="toggleRolesForm()"
						>
							<template #pagination>
								<Pagination :navigation="userNavigation" />
							</template>
						</RoleUsers>
						<RolePermissions
							:permissions="permissions"
							:role="role.id"
							class="w-full xl:flex-1"
							@close-form="togglePermissionsForm()"
						>
							<template #pagination>
								<Pagination :navigation="permissionNavigation" />
							</template>
						</RolePermissions>
					</div>
				</div>
			</div>
		</main>
		<NewModal
			:show="showAddPermissionForm"
			title="Edit Role"
			subtitle="Edit role details"
			@close="toggleAddPermissionForm()"
		>
			<AddPermissionForm
				:role="role.id"
				role-permissions="permissions"
				@form-submitted="toggleAddPermissionForm()"
			/>
		</NewModal>
	</MainLayout>
</template>
