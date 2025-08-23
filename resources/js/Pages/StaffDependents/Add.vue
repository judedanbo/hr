<script setup>
import { router } from "@inertiajs/vue3";
import { ref } from "vue";
import PersonalInformationForm from "@/Pages/Person/partials/PersonalInformationForm.vue";
import ImageUpload from "@/Pages/Person/partials/ImageUpload.vue";
import AddDependentForm from "./Create.vue";

const emit = defineEmits(["formSubmitted"]);
defineProps({
	staffId: {
		type: Number,
		required: true,
	},
	imageUrl: String,
});

const page_errors = ref(null);

const submitHandler = (data, node) => {
	const fd = new FormData();
	// fd.append('image', data.staffData.image.image[0].file)
	// const profileImage = data.staffData.image.image[0].file
	// data.staffData.personalInformation.image = profileImage
	fd.append("title", data.dependentForm.personalInformation.title ?? "");
	fd.append("surname", data.dependentForm.personalInformation.surname ?? "");
	fd.append(
		"first_name",
		data.dependentForm.personalInformation.first_name ?? "",
	);
	fd.append(
		"other_names",
		data.dependentForm.personalInformation.other_names ?? "",
	);
	fd.append(
		"date_of_birth",
		data.dependentForm.personalInformation.date_of_birth ?? "",
	);
	fd.append(
		"nationality",
		data.dependentForm.personalInformation.nationality ?? "",
	);
	fd.append("gender", data.dependentForm.personalInformation.gender ?? "");
	fd.append(
		"marital_status",
		data.dependentForm.personalInformation.marital_status ?? "",
	);
	fd.append("religion", data.dependentForm.personalInformation.religion ?? "");
	fd.append("staff_id", data.dependentForm.relation.staff_id ?? "");
	fd.append("relation", data.dependentForm.relation.relation ?? "");
	if (data.dependentForm.image.image[0]?.file) {
		fd.append("image", data.dependentForm.image.image[0].file);
	}
	// fd.append('image', data.dependentForm.image.image[0]?.file ?? '')

	// axios.post(route("dependent.store"), fd)
	//   .then(function (response) {

	//     node.reset();
	//     emit("formSubmitted");
	//   })
	//   .catch(function (error) {

	//     node.setErrors(['there are errors'],
	//     {
	//       errors
	//     }
	//     );
	//   });

	// axios.post(route("dependent.store"), data.dependentForm)

	router.post(route("dependent.store"), fd, {
		preserveState: true,
		preserveScroll: true,
		onSuccess: (message) => {
			node.reset();
			emit("formSubmitted");
		},
		onError: (errors) => {
			page_errors.value = errors;
			node.setErrors(["there are errors submitting the form"], errors);
		},
	});
};
</script>
<template>
	<main class="bg-gray-100 dark:bg-gray-700 px-8 py-8">
		<h1 class="text-2xl dark:text-gray-200">Add new Dependent</h1>
		<FormKit
			id="addDependentForm"
			type="form"
			name="addDependentForm"
			:actions="false"
			wrapper-class="mx-auto"
			@submit="submitHandler"
		>
			<!-- <Staff :steps="staff" /> -->

			<FormKit
				id="dependentForm"
				type="multi-step"
				name="dependentForm"
				:allow-incomplete="true"
				tab-style="progress"
			>
				<FormKit
					id="personalInformation"
					type="step"
					name="personalInformation"
				>
					<PersonalInformationForm />
				</FormKit>

				<FormKit id="image" type="step" name="image">
					<ImageUpload />
				</FormKit>
				<FormKit id="relation" type="step" name="relation">
					<AddDependentForm :staff-id="staffId" />
					<template #stepNext>
						<FormKit type="submit" label="Add Dependent" />
					</template>
				</FormKit>
			</FormKit>
		</FormKit>
		<p v-for="error in page_errors" class="text-xs text-rose-500">
			{{ error }}
		</p>
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
	@apply max-w-full
  /* @apply border-0 shadow-none; */;
}
.formkit-outer[data-type="multi-step"]
	> [data-tab-style="progress"]
	> .formkit-steps {
	@apply border-0 shadow-none;
}
.formkit-outer[data-type="multi-step"] > .formkit-wrapper {
	@apply max-w-full;
}
.formkit-tab-label {
	@apply dark:text-gray-200;
}
</style>
