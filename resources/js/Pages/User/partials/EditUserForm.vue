<script setup>
import { getNode } from "@formkit/core";
import { router } from "@inertiajs/vue3";
import { ref, onMounted } from "vue";

import UserRoleForm from "./UserRoleForm.vue";

const emit = defineEmits(["formSubmitted"]);
let props = defineProps({
	user: {
		type: Object,
		required: true,
	},
});

let formData = ref(null);

const submitImage = async (image) => {
	formData.value = new FormData();
	formData.value.append("image", image);
	// const avatar = await router.post(
	// 	route("person.avatar.update", { person: staff.value.person.id }),
	// 	formData.value,
	// 	{
	// 		preserveScroll: true,
	// 		onSuccess: () => {
	// 			return true;
	// 			// emit("imageUpdated");
	// 		},
	// 		onError: (errors) => {
	// 			const errorNode = getNode("image");
	// 			const errorMsg = {
	// 				"image.image": errors.image ?? "",
	// 			};
	// 			errorNode.setErrors(errors);
	// 			// errorNode = { errors: "there are errors" }; // TODO fix display server side image errors
	// 		},
	// 	},
	// );
};

const submitHandler = (data, node) => {
	router.patch(route("user.update", { user: props.user.id }), data, {
		preserveState: true,
		onSuccess: () => {
			node.reset();
			emit("formSubmitted");
		},
		onError: (errors) => {
			node.setErrors(["there are errors"], errors);
		},
		onFinish: () => {},
	});
};
</script>
<template>
	<main class="bg-gray-100 dark:bg-gray-700 px-8 py-8">
		<h1 class="text-2xl dark:text-gray-200">Edit User</h1>
		<FormKit
			id="editStaffForm"
			type="form"
			name="editStaffForm"
			submit-label="Save User"
			wrapper-class="mx-auto"
			:value="user"
			@submit="submitHandler"
		>
			<FormKit id="user_id" type="hidden" name="user_id" :value="user.id" />

			<h1
				class="mb-4 font-semibold tracking-wider text-lg text-green-800 dark:text-gray-200"
			>
				Personal Information of user
			</h1>
			<FormKit
				id="name"
				name="name"
				type="text"
				label="Name of User"
				validation="required"
				autofocus
			/>
			<FormKit
				id="email"
				name="email"
				type="email"
				label="Email Address"
				validation="required|email"
			/>
			<!-- <PersonalInformationForm /> -->
		</FormKit>
		<!-- <FormKit type="step" name="roles">
					<h1
						class="mb-4 font-semibold tracking-wider text-lg text-green-800 dark:text-gray-200"
					>
						Roles
					</h1>
					<UserRoleForm :userRoles="user.roles" />
					<template #stepNext>
						<FormKit type="submit" label="Add staff" />
					</template>
				</FormKit> -->
		<!-- <div v-else class="h-96 dark:text-white grid place-items-center">
			<img src="/images/spinner.gif" alt="spinner" />
		</div> -->
	</main>
</template>
