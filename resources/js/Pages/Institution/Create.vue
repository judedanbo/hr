<script setup>
import { Inertia } from "@inertiajs/inertia";
const emit = defineEmits(["formSubmitted"]);
import {
  format,
  addDays,
  subYears,
} from "date-fns";

const today = format(new Date(), "yyyy-MM-dd");
const start_date = format(addDays(new Date(), 1), "yyyy-MM-dd");
const end_date = format(subYears(new Date(), 5), "yyyy-MM-dd");

const submitHandler = (data, node) => {
    Inertia.post(route("institution.store"),
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
    <h1 class="text-2xl pb-4 dark:text-gray-100">Add Institution</h1>
    <FormKit @submit="submitHandler" type="form" submit-label="Add Institution">
      <FormKit
        type="text"
        name="name"
        id="name"
        validation="required|length:2,120"
        label="Institution name"
        placeholder="institution name"
        error-visibility="submit"
      />
      <div class="sm:flex gap-4">
        <FormKit
          type="text"
          name="abbreviation"
          id="abbreviation"
          validation="length:2,6"
          label="Institution abbreviation"
          placeholder="institution's abbreviation"
          error-visibility="submit"
        />
        <FormKit
          type="date"
          name="start_date"
          id="start_date"
          :value="today"
          :min="end_date"
          :max="start_date"
          label="Start date"
          :validation="
            'required|date_after:' + end_date + '|date_before:' + start_date
          "
          validation-visibility="submit"
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
