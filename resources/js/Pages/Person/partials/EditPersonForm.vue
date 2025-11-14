<script setup>
import { getNode } from "@formkit/core";
import { useForm } from "@inertiajs/vue3";
import { router } from "@inertiajs/vue3";
import { ref, onMounted } from "vue";
import PersonalInformationForm from "@/Pages/Person/partials/PersonalInformationForm.vue";
import ContactForm from "@/Pages/Person/partials/ContactForm.vue";
import ImageUpload from "@/Pages/Person/partials/ImageUpload.vue";
import { FormKitMessages } from "@formkit/vue";

const emit = defineEmits(["formSubmitted"]);
let props = defineProps({
	personId: {
		type: Number,
		required: true,
	},
});

const contact_types = ref([]);
const gender = ref([]);
const maritalStatus = ref([]);
let person = ref(null);

onMounted(async () => {
	const personData = await axios.get(
		route("person.edit", { person: props.personId }),
	);
	const { data } = await axios.get(route("contact-type.index"));
	contact_types.value = data;
	const genderData = await axios.get(route("gender.index"));
	gender.value = genderData.data;
	const maritalStatusData = await axios.get(route("marital-status.index"));
	maritalStatus.value = maritalStatusData.data;
	person.value = personData.data;
});

let formData = ref(null);

const submitImage = async (image) => {
	formData.value = new FormData();
	formData.value.append("image", image);
	const avatar = await router.post(
		route("person.avatar.update", { person: person.value.id }),
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
	router.patch(
		route("person.update", { person: props.personId }),
		data.personData.personalInformation,
		{
			preserveState: true,
			onSuccess: () => {
				node.reset();
				emit("formSubmitted");
			},
			onError: (errors) => {
				const formErrors = {};

				for (const key in errors) {
					if (Object.hasOwnProperty.call(errors, key)) {
						const element = errors[key];
						formErrors["personData.personalInformation." + key] = element;
					}
				}
				node.setErrors(["there are errors"], formErrors);
			},
			onFinish: () => {},
		},
	);

	if (data.personData.image.image[0]?.file) {
		if (submitImage(data.personData.image.image[0].file)) {
			emit("formSubmitted");
		}
	}
};
</script>
<template>
	<main class="bg-gray-100 dark:bg-gray-700 px-8 py-8">
		<h1 class="text-2xl dark:text-gray-200">Edit Person</h1>
		<FormKit
			v-if="person"
			id="editPersonForm"
			type="form"
			name="editPersonForm"
			submit-label="Edit Person"
			:actions="false"
			wrapper-class="mx-auto"
			@submit="submitHandler"
		>
			<FormKit
				id="person_id"
				type="hidden"
				name="person_id"
				:value="person.id"
			/>
			<FormKit
				type="multi-step"
				name="personData"
				:allow-incomplete="true"
				tab-style="progress"
				steps-class="pb-2"
			>
				<FormKit type="step" name="personalInformation" :value="person">
					<PersonalInformationForm />
				</FormKit>

				<FormKit id="image" type="step" name="image">
					<ImageUpload :image-url="person.image" />
					<FormKitMessages />
					<template #stepNext>
						<FormKit type="submit" label="Save" />
					</template>
				</FormKit>

				<!-- <FormKit type="step" name="contactInformation">
                     <ContactForm /> 
                    
                </FormKit> -->
			</FormKit>
		</FormKit>
		<div v-else class="h-96 dark:text-white grid place-items-center">
			<img src="/images/spinner.gif" alt="spinner" />
		</div>
	</main>
</template>
