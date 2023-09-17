<script setup>
import { ref } from "vue";
const url = ref('/images/placeholder.webp');
const imageChanged = () => {
  // console.log(e)
  // console.log(data)
  const file = profileImage.files[0];
  // console.log(file)
  if(file){
    url.value = URL.createObjectURL(file);
  }else{
    url.value = '/images/placeholder.webp';
  }
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
