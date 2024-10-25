<script setup>
import { Inertia } from "@inertiajs/inertia";
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
	dependent: {
		type: Object,
		required: true,
	},
});

const page_errors = ref(null);

const updateDependent = (data, node) => {
	const fd = new FormData();
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

	Inertia.post(
		route("dependent.update", {
			dependent: data.dependentForm.personalInformation.id,
		}),
		fd,
		{
			preserveState: true,
			onSuccess: (message) => {
				node.reset();
				emit("formSubmitted");
			},
			onError: (errors) => {
				page_errors.value = errors;
				node.setErrors(["there are errors submitting the form"], errors);
			},
		},
	);
};
</script>
<template>
	<main class="bg-gray-100 dark:bg-gray-700 px-8 py-8">
		<h1 class="text-2xl dark:text-gray-200">Edit new Dependent</h1>
		<!-- {{ dependent }} -->
		<FormKit
			type="form"
			@submit="updateDependent"
			:actions="false"
			wrapper-class="mx-auto"
		>
			<!-- <Staff :steps="staff" /> -->
			<FormKit
				type="hidden"
				id="dependent_id"
				name="dependent_id"
				:value="dependent.id"
			/>
			<FormKit
				type="multi-step"
				id="dependentForm"
				name="dependentForm"
				:allow-incomplete="true"
				tab-style="progress"
			>
				<FormKit
					type="step"
					id="personalInformation"
					name="personalInformation"
					:value="{
						id: dependent.id,
						title: dependent.title,
						surname: dependent.surname,
						first_name: dependent.first_name,
						other_names: dependent.other_names,
						date_of_birth: dependent.date_of_birth,
						gender: dependent.gender_form,
						nationality: dependent.nationality_form,
						marital_status: dependent.marital_status,
						religion: dependent.religion,
					}"
				>
					<PersonalInformationForm />
				</FormKit>
				<FormKit type="step" id="image" name="image">
					<ImageUpload :imageUrl="dependent.image" />
				</FormKit>
				<FormKit
					:value="{ relation: dependent.relation }"
					type="step"
					id="relation"
					name="relation"
				>
					<AddDependentForm :staffId="staffId" />
					<template #stepNext>
						<FormKit type="submit" label="Save Dependent" />
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
