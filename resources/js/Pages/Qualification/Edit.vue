<script setup>
import { Inertia } from "@inertiajs/inertia";
import { onMounted, ref } from "vue";
import { getNode, setErrors } from "@formkit/core";
import QualificationForm from "./partials/QualificationForm.vue";
import QualificationEvidence from "./partials/QualificationEvidence.vue";
const emit = defineEmits(["formSubmitted", "documentSubmitted"]);

const props = defineProps({
	qualification: {
		type: Object,
		required: true,
	},
});

const document = ref(null);

onMounted(() => {
	document.value = props.qualification.documents
		? props.qualification.documents[0]
		: null;
});
const submitDocuments = async (document) => {
	const formData = new FormData();
	// // document.forEach((file) => {
	formData.append("file_name", document.file_name[0].file);
	formData.append("document_type", document.document_type);
	formData.append(
		"document_title",
		document.document_title ?? document.file_name[0].name.split(".")[0],
	);
	formData.append("document_status", document.document_status ?? "P");
	formData.append("document_number", document.document_number);
	formData.append("file_type", document.file_name[0].file.type);
	// formData.append("document_file", document.document_file);
	// // });
	Inertia.post(
		route("qualification-document.update", {
			qualification: props.qualification.id,
		}),
		formData,
		{
			preserveScroll: true,
			onSuccess: () => {
				emit("documentSubmitted");
			},
			onError: (errors) => {
				const errorNode = getNode("evidence");
				const errorMsg = {
					"evidence.document_type": errors.document_type ?? "",
					"evidence.document_title": errors.document_title ?? "",
					"evidence.document_status": errors.document_status ?? "",
					"evidence.document_number": errors.document_number ?? "",
					"evidence.file_name": errors.file_name ?? "",
				};
				errorNode.setErrors(["Sever side errors"], errorMsg);
				// errorNode = { errors: "there are errors" }; // TODO fix display server side image errors
			},
		},
	);
};

const submitHandler = (data, node) => {
	Inertia.patch(
		route("qualification.update", {
			qualification: props.qualification.id,
		}),
		data,
		{
			preserveScroll: true,
			onSuccess: () => {
				node.reset();
				emit("formSubmitted");
			},
			onError: (errors) => {
				node.setErrors(["Sever side errors"], errors);
			},
		},
	);
	if (data.staffQualification.evidence.file_name.length > 0) {
		submitDocuments(data.staffQualification.evidence);
	}
};
</script>

<template>
	<main class="bg-gray-100 dark:bg-gray-700">
		<h1 class="text-2xl pb-4 dark:text-gray-100">Edit Qualification</h1>
		<FormKit type="form" :actions="false" @submit="submitHandler">
			<FormKit
				type="multi-step"
				name="staffQualification"
				:allow-incomplete="true"
				tab-style="progress"
				tabs-class="my-2"
			>
				<FormKit
					id="certification"
					name="certification"
					type="step"
					:value="qualification"
				>
					<QualificationForm />
				</FormKit>
				<FormKit
					v-if="document"
					id="evidence"
					name="evidence"
					type="step"
					step-actions-class="flex justify-between"
					:value="document"
				>
					<!-- {{ document }} -->
					<QualificationEvidence :document="document" />
					<template #stepNext>
						<FormKit type="submit" label="Save" />
					</template>
				</FormKit>
			</FormKit>
		</FormKit>
	</main>
</template>
