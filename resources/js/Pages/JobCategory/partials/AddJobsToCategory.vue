<script setup>
import { onMounted, ref } from "vue";

let jobs = ref([]);
onMounted(async () => {
  const response = await axios.get(route("job.create"));
  jobs.value = response.data;
});
</script>
<template>
  <FormKit type="form" >
    <FormKit
      type="checkbox"
      label="Terms and Conditions"
      help="Do you agree to our terms of service?"
      name="terms"
      :options="jobs"
      validation="required"
      validation-visibility="dirty"
    />
  </FormKit>
</template>
