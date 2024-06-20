<script setup>
import { getNode } from "@formkit/core";
import { useForm } from "@inertiajs/inertia-vue3";
import { Inertia } from "@inertiajs/inertia";
import { ref, onMounted, defineEmits } from "vue";
import PersonalInformationForm from "@/Pages/Person/partials/PersonalInformationForm.vue";
import ContactForm from "@/Pages/Person/partials/ContactForm.vue";
// import ImageUpload from "@/Pages/Person/partials/ImageUpload.vue";
// import EmploymentForm from "./EmploymentForm.vue";
import { FormKitMessages } from "@formkit/vue";

const emit = defineEmits(["formSubmitted"]);
let props = defineProps({
	staffId: {
		type: Number,
		required: true,
	},
});

const contact_types = ref([]);
const gender = ref([]);
const maritalStatus = ref([]);
let staff = ref(null);

onMounted(async () => {
	const StaffData = await axios.get(
		route("staff.edit", { staff: props.staffId }),
	);
	const { data } = await axios.get(route("contact-type.index"));
	contact_types.value = data;
	const genderData = await axios.get(route("gender.index"));
	gender.value = genderData.data;
	const maritalStatusData = await axios.get(route("marital-status.index"));
	maritalStatus.value = maritalStatusData.data;
	staff.value = StaffData.data;
});

let formData = ref(null);

const submitImage = async (image) => {
	formData.value = new FormData();
	formData.value.append("image", image);
	const avatar = await Inertia.post(
		route("person.avatar.update", { person: staff.value.person.id }),
		formData.value,
		{
			preserveScroll: true,
			onSuccess: () => {
				return true;
				// emit("imageUpdated");
			},
			onError: (errors) => {
				const errorNode = getNode("image");
				const errorMsg = {
					"image.image": errors.image ?? "",
				};
				errorNode.setErrors(errors);
				// errorNode = { errors: "there are errors" }; // TODO fix display server side image errors
			},
		},
	);
};

const submitHandler = (data, node) => {
	Inertia.patch(route("staff.update", { staff: props.staffId }), data, {
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

	if (data.staffData.image.image[0]?.file) {
		if (submitImage(data.staffData.image.image[0].file)) {
			emit("formSubmitted");
		}
	}
};
</script>
<template>
	<main class="bg-gray-100 dark:bg-gray-700 px-8 py-8">
		<h1 class="text-2xl dark:text-gray-200">Edit Staff</h1>
		<FormKit
			v-if="staff"
			id="addStaffForm"
			type="form"
			name="addStaffForm"
			submit-label="Add Staff"
			:actions="false"
			wrapper-class="mx-auto"
			@submit="submitHandler"
		>
			<FormKit id="staff_id" type="hidden" name="staff_id" :value="staff.id" />
			<FormKit
				type="multi-step"
				name="staffData"
				:allow-incomplete="true"
				tab-style="progress"
				steps-class="pb-2"
			>
				<FormKit type="step" name="personalInformation" :value="staff.person">
					<PersonalInformationForm />
				</FormKit>

				<FormKit id="image" type="step" name="image">
					<ImageUpload :image-url="staff.person.image" />
					<FormKitMessages />
				</FormKit>

				<!-- <FormKit type="step" name="contactInformation">
                    <ContactForm />
                </FormKit> -->
				<FormKit
					type="step"
					name="employmentInformation"
					:value="{
						hire_date: staff.hire_date,
						file_number: staff.file_number,
						staff_number: staff.staff_number,
						remarks: staff.remarks,
					}"
				>
					<!-- <EmploymentForm /> -->
					<template #stepNext>
						<FormKit type="submit" label="Save" />
					</template>
				</FormKit>
			</FormKit>
		</FormKit>
		<div v-else class="h-96 dark:text-white grid place-items-center">
			<img src="/images/spinner.gif" alt="spinner" />
		</div>
	</main>
</template>
