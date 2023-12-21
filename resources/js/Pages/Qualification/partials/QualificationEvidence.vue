<script setup>
import { ref, onMounted } from "vue";
import DocumentPreview from "./DocumentPreview.vue";
const props = defineProps({
	document: {
		type: Object,
		default: null,
	},
});
const url = ref(null);
const fileType = ref(null);

onMounted(() => {
	url.value = props.document
		? "/storage/qualifications/" + props.document.file_name
		: "/images/placeholder.webp";
	fileType.value = props.document ? props.document.file_type : null;
});

const documentChanged = () => {
	const file = file_name.files[0];
	url.value = file ? URL.createObjectURL(file) : "/images/placeholder.webp";
	fileType.value = file ? file.type : null;
};
const documentType = ref([]);
onMounted(async () => {
	const documentTypeData = await axios.get(route("document-types"));
	documentType.value = documentTypeData.data;
});
// const documentType = async () => {
// 	const documentTypeData = await axios.get(route("document-types"));
// 	return await documentTypeData.data;
// };
</script>
<template>
	<div class="flex justify-between py-2 space-x-3">
		<FormKit
			id="document_type"
			type="select"
			name="document_type"
			label="Document Type"
			placeholder="Select type of document"
			:options="documentType"
			outer-class="flex-2"
		/>
		<FormKit
			id="document_title"
			type="text"
			name="document_title"
			label="Document name"
			placeholder="Document name"
			validation="length:0,100"
			outer-class="flex-1"
		/>
	</div>
	<DocumentPreview :url="document.file_name" :type="document.file_type" />
	<FormKit
		id="file_name"
		type="file"
		name="file_name"
		accept=".pdf,.jpg,.jpeg,.png"
		validation="file"
		@input="documentChanged"
	>
	</FormKit>
</template>
