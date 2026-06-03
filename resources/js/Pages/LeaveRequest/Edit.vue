<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head } from "@inertiajs/vue3";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import LeaveRequestForm from "./partials/LeaveRequestForm.vue";

defineProps({
	request: { type: Object, required: true },
	leaveTypes: { type: Array, default: () => [] },
	planItems: { type: Array, default: () => [] },
});

const links = [
	{ name: "My Leave", url: "/leave-request" },
	{ name: "Edit", url: "" },
];
</script>

<template>
	<MainLayout>
		<Head title="Edit Leave Request" />
		<main class="max-w-3xl mx-auto sm:px-6 lg:px-8">
			<BreadCrumpVue :links="links" />
			<div class="mt-6 rounded-md bg-gray-100 dark:bg-gray-700 px-8 py-8">
				<h1 class="text-2xl pb-4 dark:text-gray-100">Edit leave request</h1>
				<LeaveRequestForm
					mode="edit"
					:request="request"
					:leave-types="leaveTypes"
					:plan-items="planItems"
				/>
			</div>
		</main>
	</MainLayout>
</template>
