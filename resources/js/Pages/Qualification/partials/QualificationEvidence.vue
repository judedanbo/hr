<script setup>
import { ref, onMounted } from "vue";
import DocumentPreview from "./DocumentPreview.vue";

const props = defineProps({
	document: {
		type: Object,
		default: null,
	},
	documentTypes: {
		type: Array,
		default: () => [],
	},
});

const url = ref(null);
const fileType = ref(null);

onMounted(() => {
	if (props.document) {
		url.value = "/storage/qualifications/" + props.document.file_name;
		fileType.value = props.document.file_type;
	} else {
		url.value = null;
		fileType.value = null;
	}
});

const documentChanged = (files) => {
	if (files && files.length > 0) {
		const file = files[0].file;
		url.value = URL.createObjectURL(file);
		fileType.value = file.type;
	} else {
		url.value = props.document
			? "/storage/qualifications/" + props.document.file_name
			: null;
		fileType.value = props.document ? props.document.file_type : null;
	}
};
</script>
<template>
	<div class="flex justify-between py-2 space-x-3">
		<FormKit
			id="document_type"
			type="select"
			name="document_type"
			label="Document Type"
			placeholder="Select type of document"
			:options="documentTypes"
			:value="document?.document_type"
			outer-class="flex-2"
		/>
		<FormKit
			id="document_title"
			type="text"
			name="document_title"
			label="Document Title"
			placeholder="e.g. Bachelor's Degree Certificate"
			validation="length:0,100"
			:value="document?.document_title"
			outer-class="flex-1"
		/>
	</div>
	<DocumentPreview :url="url" :type="fileType" />
	<FormKit
		id="file_name"
		type="file"
		name="file_name"
		label="Upload Document"
		help="Accepted formats: PDF, JPG, JPEG, PNG (max 2MB)"
		accept=".pdf,.jpg,.jpeg,.png"
		@input="documentChanged"
	/>
</template>
