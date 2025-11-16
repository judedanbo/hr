<script setup>
import RoleUsersForm from "./RoleUsersForm.vue";
import { router } from "@inertiajs/vue3";
import { ref } from "vue";

const emit = defineEmits(["formSubmitted", "close"]);

const props = defineProps({
	role: { type: Number, required: true },
});

const roleUsersFormRef = ref(null);

const submitHandler = () => {
	const selectedUsers = roleUsersFormRef.value?.selectedUsers || [];

	if (selectedUsers.length === 0) {
		alert("Please select at least one user to assign to this role.");
		return;
	}

	router.post(
		route("role.add.users", { role: props.role }),
		{ users: selectedUsers },
		{
			preserveScroll: true,
			onSuccess: () => {
				emit("formSubmitted");
			},
			onError: (errors) => {
				console.error("Error assigning users:", errors);
			},
		}
	);
};
</script>

<template>
	<main class="px-8 py-8 bg-white dark:bg-gray-800">
		<h1 class="text-2xl pb-4 font-semibold text-gray-900 dark:text-gray-100">
			Assign Users to Role
		</h1>
		<RoleUsersForm ref="roleUsersFormRef" :role-users="[]" />
		<div class="mt-6 flex gap-2">
			<button
				type="button"
				@click="submitHandler"
				class="inline-flex items-center px-4 py-2 bg-green-600 text-white font-semibold rounded-md shadow-sm hover:bg-green-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 dark:bg-gray-700 dark:hover:bg-gray-600"
			>
				Assign Users
			</button>
			<button
				type="button"
				class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-700 font-semibold rounded-md shadow-sm hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500"
				@click="$emit('close')"
			>
				Cancel
			</button>
		</div>
	</main>
</template>
