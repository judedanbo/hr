<script setup>
import { ref, computed } from "vue";
const props = defineProps({
    url: {
        type: String,
    },
    type: {
        type: String,
    },
});
const urlFileType = computed(() => {
    return props.url?.split(".")[1];
});
// onMounted(() => {
//     const urlFileType = props.url?.split(".")[1];
// });
// const url = "http://localhost/images/preview.pdf";
</script>
<template>
    {{ type }}
    <object v-if="url">
        <template v-if="type == 'application/pdf'">
            <embed :src="url" :type="type" width="100%" height="500px" />
        </template>
        <template v-else>
            <img
                :src="url"
                alt="preview document"
                class="mx-auto object-cover object-center"
            />
        </template>
    </object>
</template>
