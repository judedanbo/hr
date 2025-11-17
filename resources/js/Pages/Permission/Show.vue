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
import BreadCrump from "@/Components/BreadCrump.vue";

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

let breadcrumbLinks = [
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
	router.delete(route("permission.destroy", { permission: props.permission.id }), {
		preserveScroll: true,
		onSuccess: () => {
			router.visit(route("permission.index"));
		},
	});
};
</script>
<template>
	<Head :title="permission.name" />

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
							<div class="">
								<div
									class="mt-1 text-xl font-semibold leading-6 text-gray-900 dark:text-white"
								>
									{{ permission.display_name }}
								</div>
							</div>
						</div>
						<div
							class="flex items-center gap-x-4 sm:gap-x-6 justify-between w-full md:w-fit"
						>
							<button
								type="button"
								class="ml-auto flex items-center gap-x-1 rounded-md bg-green-600 dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600"
								@click="toggleEditPermissionForm()"
							>
								<PencilIcon class="-ml-1.5 h-5 w-5" aria-hidden="true" />
								Edit
							</button>
							<button
								type="button"
								class="flex items-center gap-x-1 rounded-md bg-red-600 dark:bg-red-700 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600"
								@click="toggleDeleteConfirmation()"
							>
								<TrashIcon class="-ml-1.5 h-5 w-5" aria-hidden="true" />
								Delete
							</button>
						</div>
					</div>
				</div>
			</header>

			<div class="mx-auto max-w-7xl py-4">
				<div
					class="mx-auto lg:grid max-w-2xl grid-cols-1 grid-rows-1 items-start lg:mx-0 px-4 lg:max-w-none lg:grid-cols-3 gap-4"
				>
					<div class="md:col-start-3 flex flex-wrap gap-4 w-full">
						<!-- Summary or metadata can go here -->
					</div>

					<div
						class="col-start-1 col-span-3 lg:col-span-2 lg:row-span-2 lg:row-end-2 space-y-4"
					>
						<PermissionRoles :roles="roles" class="w-full xl:flex-1">
							<template #pagination>
								<Pagination :navigation="roleNavigation" />
							</template>
						</PermissionRoles>
						<PermissionUsers :users="users" class="w-full xl:flex-1">
							<template #pagination>
								<Pagination :navigation="userNavigation" />
							</template>
						</PermissionUsers>
					</div>
				</div>
			</div>
		</main>
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
