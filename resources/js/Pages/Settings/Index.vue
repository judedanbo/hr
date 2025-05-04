<script setup lang="ts">
import NewAuthenticated from "@/Layouts/NewAuthenticated.vue";
import { Head, usePage } from "@inertiajs/inertia-vue3";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import { computed } from "vue";
import { UsersIcon } from "@heroicons/vue/20/solid";

const BreadcrumbLinks = [
	{
		name: "Home",
		url: "/dashboard",
	},
	{
		name: "Settings",
	},
];

const props = defineProps({
	users: { type: Number, required: true },
	admins: { type: Number, required: true },
	staff: { type: Number, required: true },
	roles: { type: Number, required: true },
	hrUser: { type: Number, required: true },
	permissions: { type: Number, required: true },
});

const page = usePage();
const permissions = computed(() => page.props.value.auth.permissions);
</script>

<template>
	<NewAuthenticated>
		<Head title="Next Promotion list" />
		<main class="max-w-7xl mx-auto sm:px-6 lg:px-8">
			<BreadCrumpVue :links="BreadcrumbLinks" />
			<div class="flex gap-x-4 text-2xl m-4 text-gray-700 dark:text-gray-50">
				<div>Users: {{ props.users }}</div>
				<div>HR User: {{ props.hrUser }}</div>
				<div>Staff: {{ props.staff }}</div>
				<div>Roles: {{ props.roles }}</div>
				<div>permissions: {{ props.permissions }}</div>
			</div>
		</main>
	</NewAuthenticated>
</template>
