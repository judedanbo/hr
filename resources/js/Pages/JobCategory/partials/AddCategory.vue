<script setup>
import { Inertia } from "@inertiajs/inertia";
import { onMounted, ref } from "vue";
import {
  format,
  addDays,
  subYears,
} from "date-fns";

const emit = defineEmits(["formSubmitted"]);

let categories = ref([]);
let institution = ref([]);
onMounted(async () => {
    const response = await axios.get(route("job-category.create"));
    categories.value = response.data;
    console.log(categories.value);
});

onMounted(async () => {
    const response = await axios.get(route("institution.create"));
    institution.value = response.data;
    console.log(institution.value);
});



const today = format(new Date(), "yyyy-MM-dd");
const start_date = format(addDays(new Date(), 1), "yyyy-MM-dd");
const end_date = format(subYears(new Date(), 1), "yyyy-MM-dd");

const submitHandler = (data, node) => {
    Inertia.post(route("job-category.store"),
    data, {
        preserveScroll: true,
        onSuccess: () => {
            node.reset()
            emit("formSubmitted");
        },
        onError: (errors) => {
            node.setErrors(['Error on submission'], errors)
        }
    })
}

</script>

<template>
  <main class="px-8 py-8 bg-gray-100 dark:bg-gray-700">
    <h1 class="text-2xl pb-4 dark:text-gray-100">Add Category</h1>
    <FormKit @submit="submitHandler" type="form" submit-label="Save">
        <FormKit
        type="select"
        name="institution_id"
        id="institution_id"
        validation="number|min:1|max:1000"
        placeholder="Select institution"
        label="Institution"
        :options="institution"
 
        error-visibility="submit" 
      />
        <FormKit
        type="text"
        name="name"
        id="name"
        label="Category name"
        validation="required|string|length:2,100"
        validation-visibility="submit" />
      <FormKit
        type="text"
        name="short_name"
        id="short_name"
        label="Abbreviation"
        validation="required|string|length:2,10"
        validation-visibility="submit" />
      <FormKit
        type="number"
        name="level"
        id="level"
        label="Level"
        validation="number|min:1|max:100"
        validation-visibility="submit" />
     
      <FormKit
        type="select"
        name="job_category_id"
        id="job_category_id"
        placeholder="Select parent category"
        validation="number|min:1|max:100"
        label="Next Level"
        :options="categories"
        error-visibility="submit" 
      />
     
     
      <div class="sm:flex gap-4">
       
        <FormKit
          type="date"
          name="start_date"
          id="start_date"
          :min="end_date"
          :max="start_date"
          label="Start date"
          :validation="
            'date_after:' + end_date + '|date_before:' + start_date
          "
          validation-visibility="submit"
          inner-class="w-1/2"
        />
      </div>
    </FormKit>
  </main>
</template>

<style scoped>
.formkit-outer {
  @apply w-full;
}
.formkit-submit {
  @apply justify-self-end;
}
.formkit-actions {
  @apply flex justify-end;
}
</style>
