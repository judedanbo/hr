<script setup>
import UserRoleForm from "./UserRoleForm.vue";
import { Inertia } from "@inertiajs/inertia";
import axios from "axios";
import { computed } from "vue";
import { onMounted, ref } from "vue";
const emit = defineEmits(["formSubmitted"]);

const props = defineProps({
	user: { type: Number, required: true },
});

const roles = ref([]);
const userRoles = ref([]);

onMounted(async () => {
	const response = await axios.get(route("roles.list"));
	roles.value = response.data;

	const response2 = await axios.get(route("user.roles", { user: props.user }));
	userRoles.value = response2.data;
});

const submitHandler = (data, node) => {
	console.log(data);
	Inertia.post(route("user.add.roles", { user: props.user }), data, {
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
// const userRoles = computed(() => {
// 	return roles.value.map(function (role) {
// 		return role.value;
// 	});
// });
</script>

<template>
	<main class="px-8 py-8 bg-gray-100 dark:bg-gray-700">
		<h1 class="text-2xl pb-4 dark:text-gray-100">Roles</h1>
		<!-- {{ userRoles }} -->
		<FormKit type="form" submit-label="Save" @submit="submitHandler">
			<UserRoleForm :userRoles="userRoles" />
		</FormKit>
	</main>
</template>
