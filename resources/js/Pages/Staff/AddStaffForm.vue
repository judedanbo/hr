<script setup>
import { Inertia } from "@inertiajs/inertia";
import { ref, onMounted } from "vue";
import axios from "axios";
import EmploymentForm from "./partials/EmploymentForm.vue";
import PersonalInformationForm from '@/Pages/Person/partials/PersonalInformationForm.vue'
import ContactForm from '@/Pages/Person/partials/ContactForm.vue'

const emit = defineEmits(["formSubmitted"]);

const contact_types = ref([]);
const gender = ref([]);
const maritalStatus = ref([]);
onMounted(async () => {
	const { data } = await axios.get(route("contact-type.index"));
	contact_types.value = data;
	const genderData = await axios.get(route("gender.index"));
	gender.value = genderData.data;
	const maritalStatusData = await axios.get(route("marital-status.index"));
	maritalStatus.value = maritalStatusData.data;
});

// onMounted(async() =>{
//   const {gender} = await axios.get(route('gender.index'))
// })

const submitHandler = (data, node) => {
	Inertia.post(route("staff.store"), data , {
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
	<main class="bg-gray-100 dark:bg-gray-700 px-8 py-8">
		<h1 class="text-2xl dark:text-gray-200">Add new Staff</h1>
		<FormKit
			id="addStaffForm"
			type="form"
			name="addStaffForm"
			value="formData"
			submit-label="Add Staff"
			:actions="false"
			wrapper-class="mx-auto"
			@submit="submitHandler"
		>
			<!-- <Staff :steps="stepNames" /> -->
			<FormKit
				type="multi-step"
				name="staffData"
				:allow-incomplete="true"
				tab-style="progress"
			>
				<FormKit type="step" name="personalInformation">
					<PersonalInformationForm />
				</FormKit>

				<FormKit type="step" name="contactInformation">
					<ContactForm />
				</FormKit>
				<FormKit type="step" name="employmentInformation">
					<EmploymentForm />
					<template #stepNext>
						<FormKit type="submit" label="Add staff" />
					</template>
				</FormKit>
			</FormKit>
		</FormKit>
	</main>
</template>

<style>
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
}
.formkit-tab-label {
	@apply dark:text-gray-200;
}
</style>
