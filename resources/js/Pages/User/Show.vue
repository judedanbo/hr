<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, usePage, router } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import { useToggle } from "@vueuse/core";
import BreadCrump from "@/Components/BreadCrump.vue";
import NoPermission from "@/Components/NoPermission.vue";
import NewModal from "@/Components/NewModal.vue";
import UserIdentityCard from "@/Components/User/UserIdentityCard.vue";
import UserAccountCard from "@/Components/User/UserAccountCard.vue";
import UserStaffRecordCard from "@/Components/User/UserStaffRecordCard.vue";
import UserRoles from "./partials/UserRoles.vue";
import UserPermissions from "./partials/UserPermissions.vue";
import AssociateStaff from "./partials/AssociateStaff.vue";

const props = defineProps({
	user: { type: Object, default: () => null },
});

const page = usePage();
const permissions = computed(() => page.props?.auth.permissions);

const breadcrumbLinks = [
	{ name: "Dashboard", url: "/dashboard" },
	{ name: "Users", url: "/user" },
	{ name: props.user.name, url: null },
];

const openAssociateModal = ref(false);
const toggleAssociateModal = useToggle(openAssociateModal);

const unlinkStaff = () => {
	if (
		!window.confirm(
			"Unlink this user from their staff record? This also removes the staff role.",
		)
	) {
		return;
	}
	router.delete(route("user.dissociate-staff", { user: props.user.id }), {
		preserveScroll: true,
	});
};
</script>

<template>
	<Head :title="user.name" />
	<MainLayout>
		<main
			v-if="permissions?.includes('view user')"
			class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-6"
		>
			<BreadCrump :links="breadcrumbLinks" />

			<UserIdentityCard :user="user" class="mt-4" />

			<div class="mt-5 grid grid-cols-1 lg:grid-cols-3 gap-5">
				<!-- Left rail -->
				<div class="lg:col-span-1 flex flex-col gap-5">
					<UserAccountCard :user="user" />
					<UserStaffRecordCard
						:user="user"
						:can-manage="permissions?.includes('associate user staff')"
						@associate="toggleAssociateModal()"
						@unlink="unlinkStaff"
					/>
				</div>

				<!-- Right main -->
				<div class="lg:col-span-2 flex flex-col gap-5">
					<UserRoles
						:roles="user.roles"
						:user="user.id"
						:can-add="permissions?.includes('assign roles to user')"
						:has-staff-record="!!user.person_id"
					/>
					<UserPermissions
						:user="user.id"
						:direct-permissions="user.direct_permissions"
						:inherited-permissions="user.inherited_permissions"
						:can-add="permissions?.includes('assign permissions to user')"
					/>
				</div>
			</div>

			<NewModal :show="openAssociateModal" @close="toggleAssociateModal()">
				<AssociateStaff
					:user="user.id"
					@form-submitted="toggleAssociateModal()"
				/>
			</NewModal>
		</main>
		<NoPermission v-else />
	</MainLayout>
</template>
