<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, usePage } from "@inertiajs/inertia-vue3";
import Summary from "@/Pages/Person/Summary.vue";
import UserRoles from "./partials/UserRoles.vue";
import UserPermissions from "./partials/UserPermissions.vue";
import { PlusIcon } from "@heroicons/vue/24/outline";
import BreadCrump from "@/Components/BreadCrump.vue";

import { useToggle } from "@vueuse/core";
import { ref, computed } from "vue";

import NewModal from "@/Components/NewModal.vue";
import EditStaffForm from "./EditStaffForm.vue";
import EditContactForm from "./EditContactForm.vue";
import NoItem from "@/Components/NoItem.vue";
import NoPermission from "@/Components/NoPermission.vue";

let showPromotionForm = ref(false);
let showTransferForm = ref(false);
let openEditModal = ref(false);

let toggle = useToggle(openEditModal);

let togglePermissionsForm = useToggle(showPromotionForm);
let toggleTransferForm = useToggle(showTransferForm);

let props = defineProps({
	user: { type: Object, default: () => null },
});

let breadcrumbLinks = [
	{ name: "Dashboard", url: "" },
	{ name: "Users", url: "/user" },
	{ name: props.user.name },
	4, // { name: props.person.name, url: "/" },
];
const page = usePage();
const permissions = computed(() => {
	return page.props.value.auth.permissions;
});
const openEditContact = ref(false);
const toggleEditContactModal = useToggle(openEditContact);
// const confirmDelete = useToggle(openEditModal);

const editContactModal = () => {
	openEditContact.value = !openEditContact.value;
};
</script>
<template>
	<Head :title="user.name" />

	<MainLayout>
		<main v-if="permissions.includes('view user')">
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
									{{ user.name }}
								</div>
							</div>
						</div>
						<div
							class="flex items-center gap-x-4 sm:gap-x-6 justify-between w-full md:w-fit"
						>
							<button
								v-if="permissions.includes('assign roles to user')"
								type="button"
								class="hidden text-sm font-semibold leading-6 text-green-900 dark:text-white sm:block"
								@click="togglePermissionsForm()"
							>
								Add Roles
							</button>
							<button
								v-if="permissions.includes('assign permissions to user')"
								type="button"
								class="hidden text-sm font-semibold leading-6 text-green-900 dark:text-white sm:block"
								@click="toggleTransferForm()"
							>
								Add Permissions
							</button>

							<!-- <a
								href="#"
								class="ml-auto flex items-center gap-x-1 rounded-md bg-green-600 dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600"
								@click.prevent="toggle()"
							>
								<PlusIcon class="-ml-1.5 h-5 w-5" aria-hidden="true" />
								Edit
							</a> -->
						</div>
					</div>
				</div>
			</header>

			<div class="mx-auto max-w-7xl py-4">
				<div class="mx-auto max-w-2xl items-start lg:mx-0 px-4 gap-4">
					<div class="flex flex-col md:flex-row gap-4 w-full">
						<UserPermissions
							:user="user.id"
							:permissions="user.permissions"
							:can-add="permissions.includes('assign permissions to user')"
							class="w-full md:w-3/5"
							@close-form="togglePermissionsForm()"
						/>
						<!-- <NoPermission v-else /> -->
						<UserRoles
							:roles="user.roles"
							:user="user.id"
							class="flex-1"
							:canAdd="permissions.includes('assign roles to user')"
							@close-form="toggleRolesForm()"
						/>
					</div>
				</div>
			</div>
			<NewModal :show="openEditModal" @close="toggle()">
				<EditStaffForm :staff-id="staff.staff_id" @form-submitted="toggle()" />
			</NewModal>

			<NewModal :show="openEditContact" @close="toggleEditContactModal()">
				<!-- {{ contact }} -->
				<EditContactForm
					:contact="staff.staff_id"
					@form-submitted="(model) => editContactModal(model)"
				/>
			</NewModal>
		</main>
		<NoPermission v-else />
	</MainLayout>
</template>
