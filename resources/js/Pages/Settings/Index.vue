<script setup>
import NewAuthenticated from "@/Layouts/NewAuthenticated.vue";
import { Head, usePage } from "@inertiajs/vue3";
import BreadCrump from "@/Components/BreadCrump.vue";
import SettingCard from "@/Components/Settings/SettingCard.vue";
import RecentActivityCard from "@/Components/Settings/RecentActivityCard.vue";
import { computed } from "vue";
import {
	UsersIcon,
	ShieldCheckIcon,
	KeyIcon,
	ClipboardDocumentListIcon,
	BuildingOffice2Icon,
} from "@heroicons/vue/24/outline";

const props = defineProps({
	stats: { type: Object, required: true },
	recentActivity: { type: Array, default: () => [] },
});

const page = usePage();
const permissions = computed(() => page.props?.auth.permissions);
const can = (permission) => permissions.value?.includes(permission);

const breadcrumbLinks = [
	{ name: "Home", url: "/dashboard" },
	{ name: "Settings", url: null },
];

const cards = computed(() =>
	[
		{
			title: "Users",
			count: props.stats.users,
			secondary: `${props.stats.staff} staff · ${props.stats.hrUser} HR`,
			href: route("user.index"),
			linkLabel: "Manage",
			icon: UsersIcon,
			gate: "view all users",
		},
		{
			title: "Roles",
			count: props.stats.roles,
			href: route("role.index"),
			linkLabel: "Manage",
			icon: ShieldCheckIcon,
			gate: "view roles",
		},
		{
			title: "Permissions",
			count: props.stats.permissions,
			href: route("permission.index"),
			linkLabel: "Manage",
			icon: KeyIcon,
			gate: "view permissions",
		},
		{
			title: "Audit Log",
			count: props.stats.auditLogs,
			href: route("audit-log.index"),
			linkLabel: "View",
			icon: ClipboardDocumentListIcon,
			gate: "view user activity",
		},
		{
			title: "Institutions",
			count: props.stats.institutions,
			href: route("institution.index"),
			linkLabel: "Manage",
			icon: BuildingOffice2Icon,
			gate: "view admin settings",
		},
	].filter((card) => can(card.gate)),
);
</script>

<template>
	<Head title="Settings" />
	<NewAuthenticated>
		<main class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-6">
			<BreadCrump :links="breadcrumbLinks" />

			<div class="mt-4">
				<h1
					class="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-50"
				>
					Settings
				</h1>
				<p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
					Manage users, roles, permissions, and related administration.
				</p>
			</div>

			<div class="mt-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
				<SettingCard
					v-for="card in cards"
					:key="card.title"
					:title="card.title"
					:count="card.count"
					:secondary="card.secondary"
					:href="card.href"
					:link-label="card.linkLabel"
					:icon="card.icon"
				/>
			</div>

			<RecentActivityCard
				v-if="can('view user activity')"
				:activities="recentActivity"
				class="mt-6"
			/>
		</main>
	</NewAuthenticated>
</template>
