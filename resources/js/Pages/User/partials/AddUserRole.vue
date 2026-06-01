<script setup>
import UserRoleForm from "./UserRoleForm.vue";
import { router } from "@inertiajs/vue3";
import axios from "axios";
import { onMounted, ref } from "vue";
const emit = defineEmits(["formSubmitted"]);

const props = defineProps({
	user: { type: Number, required: true },
	hasStaffRecord: { type: Boolean, default: false },
});

const userRoles = ref([]);

onMounted(async () => {
	const response = await axios.get(route("user.roles", { user: props.user }));
	userRoles.value = response.data;
});

const submitHandler = (data, node) => {
	router.post(route("user.add.roles", { user: props.user }), data, {
		preserveScroll: true,
		onSuccess: () => {
			node.reset();
			emit("formSubmitted");
		},
		onError: (errors) => {
			node.setErrors([""], errors);
		},
	});
};
</script>

<template>
	<main class="px-8 py-8 bg-white dark:bg-gray-800">
		<h1 class="text-xl font-semibold pb-4 text-green-900 dark:text-gray-100">
			Roles
		</h1>
		<FormKit type="form" submit-label="Save" @submit="submitHandler">
			<UserRoleForm :user-roles="userRoles.roles" :has-staff-record="props.hasStaffRecord" />
		</FormKit>
	</main>
</template>
