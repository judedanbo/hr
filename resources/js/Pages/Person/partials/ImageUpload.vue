<script setup>
import { ref, onMounted } from "vue";
const props = defineProps({
    imageUrl: String,
});
const url = ref(null);

onMounted(() => {
    url.value = props.imageUrl
        ? "/storage/images/" + props.imageUrl
        : "/images/placeholder.webp";
});
// const url = ref(props.imageUrl ?? "/images/placeholder.webp");

const imageChanged = () => {
    const file = profileImage.files[0];
    url.value = file ? URL.createObjectURL(file) : "/images/placeholder.webp";
};
</script>
<template>
    <div>
        <div class="py-4">
            <img
                :src="url"
                alt="preview profile image"
                class="w-56 h-56 mx-auto object-cover object-center rounded-full"
            />
        </div>
        <FormKit
            @input="imageChanged"
            id="profileImage"
            type="file"
            name="image"
            accept="image/*"
            validation="image"
        >
        </FormKit>
    </div>
</template>
