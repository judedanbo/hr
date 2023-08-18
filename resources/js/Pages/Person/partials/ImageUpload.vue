<script setup>
import { ref } from "vue";
const url = ref(null);
// let selectedFile = null;
const imageChanged = (e) => {
  // this.selectedFile = e.target.files[0];

  const file = e.target.files[0];
  url.value = URL.createObjectURL(file);
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
      @Change="imageChanged"
      type="file"
      name="image"
      id="image"
      label="Select Image"
      accept="image/*"
      validation="image"
      inner-class="opacity-0"
    />
  </div>
</template>
