<script setup>
import { Inertia } from "@inertiajs/inertia";
import { ref, onMounted } from "vue";
import axios from "axios";
import UserRoleForm from "./UserRoleForm.vue";
import { FormKit } from "@formkit/vue";

const emit = defineEmits(["formSubmitted"]);

const submitHandler = (data, node) => {
	Inertia.post(route("user.store"), data, {
		preserveState: true,
		onSuccess: () => {
			node.reset();
			emit("formSubmitted");
		},
		onError: (errors) => {
			node.setErrors(["there are errors in the form"], errors);
		},
	});
};
</script>
<template>
	<main class="bg-gray-100 dark:bg-gray-700">
		<h1
			class="text-2xl font-semibold tracking-wider text-green-800 dark:text-gray-50 px-10"
		>
			Add new user
		</h1>
		<FormKit
			id="addUserForm"
			type="form"
			name="addUserForm"
			value="formData"
			submit-label="Add User"
			:actions="false"
			wrapper-class="mx-auto"
			@submit="submitHandler"
		>
			<!-- <Staff :steps="stepNames" /> -->
			<FormKit
				type="multi-step"
				name="userData"
				:allow-incomplete="true"
				tab-style="progress"
				outer-class="px-0 mb-0"
				wrapper-class="max-w-2xl"
			>
				<FormKit type="step" name="bio" outer-class="p-0">
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
				<FormKit type="step" name="roles">
					<h1
						class="mb-4 font-semibold tracking-wider text-lg text-green-800 dark:text-gray-200"
					>
						Roles
					</h1>
					<UserRoleForm />
					<template #stepNext>
						<FormKit type="submit" label="Add staff" />
					</template>
				</FormKit>
			</FormKit>
		</FormKit>
	</main>
</template>

<style>
.formkit-outer[data-type="multi-step"]
	> [data-tab-style="progress"]
	> .formkit-tabs {
	margin-top: 1em;
}
.formkit-outer[data-type="multi-step"] > .formkit-wrapper {
	max-width: 36em;
}

.formkit-outer[data-type="multi-step"] > .formkit-wrapper > .formkit-steps {
	padding-top: 0.5em;
}

.formkit-form {
	/* @apply mx-8 mb-4; */
}
.formkit-wrapper {
	@apply mx-auto;
}
.formkit-step {
	@apply border-0 shadow-none;
}
.formkit-outer[data-type="multi-step"]
	> [data-tab-style="progress"]
	> .formkit-steps {
	@apply border-0 shadow-none;
	@apply max-w-xl;
}
.formkit-tab-label {
	@apply dark:text-gray-200;
}
</style>
