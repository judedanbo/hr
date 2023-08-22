<script setup>
import { Inertia } from "@inertiajs/inertia";
import { onMounted, ref } from "vue";
const emit = defineEmits(["formSubmitted"]);

const props = defineProps({
  staff: Number,
  institution: Number,
})

let statuses = ref([]);

onMounted(async () => {
    const response = await axios.get(route("institution.statuses", {institution: props.institution}));
    statuses.value = response.data;
    console.log(statuses.value);
});


import {
  format,
  addDays,
  subYears,
} from "date-fns";

const today = format(new Date(), "yyyy-MM-dd");
const start_date = format(addDays(new Date(), 1), "yyyy-MM-dd");
const end_date = format(subYears(new Date(), 1), "yyyy-MM-dd");

const submitHandler = (data, node) => {
    Inertia.post(route("staff-status.save", { staff: data.staff_id }),
    data, {
        preserveScroll: true,
        onSuccess: () => {
            node.reset()
            emit("formSubmitted");
        },
        onError: (errors) => {
            node.setErrors([''], errors)
        }
    })
}

</script>

<template>
  <main class="px-8 py-8 bg-gray-100 dark:bg-gray-700">
    <h1 class="text-2xl pb-4 dark:text-gray-100">Change Status</h1>
    <FormKit @submit="submitHandler" type="form" submit-label="Save">
      <FormKit type="hidden" name="staff_id" :value="staff" />
      <FormKit type="hidden" name="institution_id" :value="institution" />
      <FormKit
        type="select"
        name="status"
        id="status"
        validation="required|string"
        label="Status"
        placeholder="Select Status"
        :options="statuses"
        error-visibility="submit"
      />
      <div class="sm:flex gap-4">
       
        <FormKit
          type="date"
          name="start_date"
          id="start_date"
          :value="today"
          :min="end_date"
          :max="start_date"
          label="Start date"
          :validation="
            'required|date|date_after:' + end_date + '|date_before:' + start_date
          "
          validation-visibility="submit"
          inner-class="w-1/2"
        />
        <FormKit
          type="date"
          name="end_date"
          id="end_date"
          :min="today"
          label="End date"
          :validation="
            'date|date_after:' + today"
          validation-visibility="submit"
          inner-class="w-1/2"
        />
      </div>
      <FormKit
        type="text"
        name="description"
        id="description"
        label="Description"
        validation="string|length:2,120"
        validation-visibility="submit" />
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
