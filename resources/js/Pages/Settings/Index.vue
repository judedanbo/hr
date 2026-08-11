<script setup>
import NewAuthenticated from "@/Layouts/NewAuthenticated.vue";
import { Head, usePage } from "@inertiajs/vue3";
import PageShell from "@/Components/UI/PageShell.vue";
import PageHeader from "@/Components/UI/PageHeader.vue";
import SettingCard from "@/Components/Settings/SettingCard.vue";
import RecentActivityCard from "@/Components/Settings/RecentActivityCard.vue";
import { computed } from "vue";
import {
	UsersIcon,
	ShieldCheckIcon,
	KeyIcon,
	ClipboardDocumentListIcon,
	BuildingOffice2Icon,
	Cog6ToothIcon,
	ShieldExclamationIcon,
} from "@heroicons/vue/24/outline";

const props = defineProps({
	stats: { type: Object, required: true },
	recentActivity: { type: Array, default: () => [] },
});

const page = usePage();
const permissions = computed(() => page.props?.auth.permissions);
const can = (permission) => permissions.value?.includes(permission);

const breadcrumbLinks = [{ name: "Settings", url: null }];

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
		{
			title: "Data Integrity",
			secondary: "Find and fix inconsistent records",
			href: route("data-integrity.index"),
			linkLabel: "Review",
			icon: ShieldExclamationIcon,
			gate: "data-integrity.view",
		},
		{
			title: "Application",
			secondary: "Name, email, display, security",
			href: route("app-settings.edit"),
			linkLabel: "Configure",
			icon: Cog6ToothIcon,
			gate: "update app settings",
		},
	].filter((card) => can(card.gate)),
);
</script>

<template>
	<Head title="Settings" />
	<NewAuthenticated>
		<PageShell>
			<PageHeader
				title="Settings"
				description="Manage users, roles, permissions, and related administration."
				:breadcrumbs="breadcrumbLinks"
			/>

			<div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
				<SettingCard
					v-for="card in cards"
					:key="card.title"
					:title="card.title"
					:count="card.count ?? null"
					:secondary="card.secondary"
					:href="card.href"
					:link-label="card.linkLabel"
					:icon="card.icon"
				/>
			</div>

			<RecentActivityCard
				v-if="can('view user activity')"
				:activities="recentActivity"
			/>
		</PageShell>
	</NewAuthenticated>
</template>
