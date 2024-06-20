<script setup>
import { Inertia } from "@inertiajs/inertia";
import { onMounted, ref } from "vue";
const emit = defineEmits(["formSubmitted"]);

const props = defineProps({
	user: { type: Number, required: true },
	// userRoles: {
	// 	type: Array,
	// 	default: () => [],
	// },
});

import { format, addDays, subYears } from "date-fns";

const today = format(new Date(), "yyyy-MM-dd");
const start_date = format(addDays(new Date(), 1), "yyyy-MM-dd");
const end_date = format(subYears(new Date(), 20), "yyyy-MM-dd");

let roles = ref([]);

onMounted(async () => {
	const response = await axios.get(route("roles.list"));
	roles.value = response.data;
});

const submitHandler = (data, node) => {
	Inertia.post(route("user.add.roles", { user: data.user }), data, {
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
const userRoles = ref([]);
</script>

<template>
	<main class="px-8 py-8 bg-gray-100 dark:bg-gray-700">
		<h1 class="text-2xl pb-4 dark:text-gray-100">Roles</h1>
		<FormKit type="form" submit-label="Save" @submit="submitHandler">
			<FormKit type="hidden" id="user" name="user" :value="user" />
			<FormKit
				v-model="userRoles"
				type="checkbox"
				name="roles"
				id="roles"
				validation="required|integer|min:1|max:2000"
				label="New role"
				placeholder="Select new Rank"
				:options="roles"
				error-visibility="submit"
			/>
		</FormKit>
	</main>
</template>
