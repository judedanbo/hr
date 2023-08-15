<script setup>
import { ref } from "vue";
const url = ref(null);
const imageChanged = (e) => {
  console.log("changed");
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
        class="w-56 mx-auto"
      />
    </div>
    <FormKit
      @Change="imageChanged"
      type="file"
      name="image"
      id="image"
      placeholder="Select Image"
      accept="image/*"
      validation="image"
    />
  </div>
</template>
