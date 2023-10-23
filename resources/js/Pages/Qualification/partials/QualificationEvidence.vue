<script setup>
import { ref, onMounted } from "vue";
import DocumentPreview from "./DocumentPreview.vue";
const props = defineProps({
    documents: Object,
});
const url = ref(null);
const fileType = ref(null);
const documentType = ref(null);

onMounted(() => {
    url.value =
        props.documents.length > 0
            ? "/storage/qualifications/" + props.documents[1].document_file
            : "/images/placeholder.webp";
    fileType.value =
        props.documents.length > 0 ? props.documents[1].file_type : null;
});

const documentChanged = () => {
    const file = documentUpload.files[0];
    url.value = file ? URL.createObjectURL(file) : "/images/placeholder.webp";
    fileType.value = file ? file.type : null;
};

const documentType = async () => {
  const documentTypeData = await axios.get(route("document-types"));
  return await documentTypeData.data;
};
</script>
<template>
    <div>
        <!-- {{ url }} -->
        <!-- <div class="py-4">
            <img
                :src="url"
                alt="preview profile document"
                class="w-56 h-56 mx-auto object-cover object-center rounded-full"
            />
        </div> -->
        <!-- {{ fileType }} -->
        <DocumentPreview :url="url" :type="fileType" />
        <div class="flex justify-between py-2 space-x-3">
            <FormKit
                type="select"
                id="document_type"
                name="document_type"
                label="Document Type"
                :options="documentType"
                outer-class="flex-2"
            />
            <FormKit
                type="text"
                name="document_title"
                id="document_title"
                label="Document name"
                placeholder="Document name"
                validation="required|length:0,100"
                outer-class="flex-1"
            />
        </div>
        <FormKit
            @input="documentChanged"
            id="documentUpload"
            type="file"
            name="documentUpload"
            accept=".pdf,.jpg,.jpeg,.png"
            validation="file"
        >
        </FormKit>
    </div>
</template>
