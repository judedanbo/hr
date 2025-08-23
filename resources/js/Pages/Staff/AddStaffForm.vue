<script setup>
import { router } from "@inertiajs/vue3";
import { ref, onMounted } from "vue";
import axios from "axios";
import AddressForm from "@/Pages/Person/partials/AddressForm.vue";
import EmploymentForm from "./partials/EmploymentForm.vue";
import PersonalInformationForm from "@/Pages/Person/partials/PersonalInformationForm.vue";
import ContactForm from "@/Pages/Person/partials/ContactForm.vue";
import AssignRank from "./partials/AssignRank.vue";
import AssignUnit from "./partials/AssignUnit.vue";
import QualificationForm from "@/Pages/Qualification/partials/QualificationForm.vue";

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
	router.post(route("staff.store"), data, {
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
		<h1 class="text-2xl font-semibold tracking-wider text-green-800 dark:text-gray-50 px-10">Add new Staff</h1>
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
				outer-class="px-0 mb-0"
				wrapper-class="max-w-2xl"
				
			>
				<FormKit type="step" name="bio" outer-class="p-0" >
					<h1 class="mb-4 font-semibold tracking-wider text-lg text-green-800 dark:text-gray-200">
						Personal Information of Staff
					</h1>
					<PersonalInformationForm />
				</FormKit>

				<FormKit type="step" name="address">
					<div>
						<h1
							class="mb-4 font-semibold tracking-wider text-lg text-green-800 dark:text-gray-200"
						>
							Address of Staff
						</h1>
						<AddressForm />
					</div>
				</FormKit>
				<FormKit type="step" name="contact">
					<div>
						<h1
							class="mb-4 font-semibold tracking-wider text-lg text-green-800 dark:text-gray-200"
						>
							Staff Contact
						</h1>
						<ContactForm />
					</div>
				</FormKit>

				<FormKit type="step" name="qualifications">
					<h1 class="mb-4 font-semibold tracking-wider text-lg text-green-800 dark:text-gray-200">
						Professional and Academic qualifications
					</h1>
					<QualificationForm />
				</FormKit>
				<FormKit type="step" name="employment">
					<h1 class="mb-4 font-semibold tracking-wider text-lg text-green-800 dark:text-gray-200">
						Employment information
					</h1>
					<EmploymentForm />
				</FormKit>
				<FormKit type="step" name="rank">
					<h1 class="mb-4 font-semibold tracking-wider text-lg text-green-800 dark:text-gray-200">
						Rank Employment to
					</h1>
					<AssignRank :institution="1" />
				</FormKit>
				<FormKit type="step" name="unit">
					<h1 class="mb-4 font-semibold tracking-wider text-lg text-green-800 dark:text-gray-200">
						Unit Assigned
					</h1>
					<AssignUnit :institution="1" />
					<template #stepNext>
						<FormKit type="submit" label="Add staff" />
					</template>
				</FormKit>
			</FormKit>
		</FormKit>
	</main>
</template>

<style>
.formkit-outer[data-type="multi-step"] > [data-tab-style="progress"] > .formkit-tabs {
	margin-top: 1em
}
.formkit-outer[data-type="multi-step"] > .formkit-wrapper {
	max-width: 36em
}

.formkit-outer[data-type="multi-step"] > .formkit-wrapper > .formkit-steps {
	padding-top: 0.5em
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
