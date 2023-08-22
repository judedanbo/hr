<script setup>
import { Inertia } from "@inertiajs/inertia";
const emit = defineEmits(["formSubmitted"]);

import { format, addYears, subYears } from "date-fns";

let props = defineProps({
  institutionName: String,
  institutionId: Number,
  allUnits: Array,
  unitTypes: Array,
});
const today = format(new Date(), "yyyy-MM-dd");
const start_date = format(subYears(new Date(), 1), "yyyy-MM-dd");
const end_date = format(addYears(new Date(), 1), "yyyy-MM-dd");

props.allUnits.unshift({
  value: null,
  label: "Select parent unit",
});
props.unitTypes.unshift({
  value: null,
  label: "Select unit type",
});

const submitHandler = (data, node) => {
  Inertia.post(route("unit.store"), data, {
    preserveScroll: true,
    onSuccess: () => {
      node.reset();
      emit("formSubmitted");
    },
    onError: (errors) => {
      node.setErrors(["errors"], errors);
    },
  });
};
</script>

<template>
  <main class="px-8 py-8 bg-gray-100 dark:bg-gray-700">
    <h1 class="text-2xl pb-4 dark:text-gray-100">
      Add new Department/Section/Unit
    </h1>
    <FormKit @submit="submitHandler" type="form" submit-label="Save">
      <FormKit
        type="hidden"
        name="institution_id"
        id="institution_id"
        :value="institutionId"
      />
      <FormKit
        type="text"
        name="name"
        id="name"
        label="Name of department/sec/unit"
        validation="required|string|length:2,100"
        validation-visibility="submit"
      />
      <FormKit
        type="select"
        name="type"
        id="type"
        :options="unitTypes"
        label="Parent unit type"
        validation="string|length:1,5"
        validation-visibility="submit"
      />
      <FormKit
        type="hidden"
        name="institution"
        id="institution"
        :value="institutionName"
        validation="string|length:2,100"
        validation-visibility="submit"
        disabled="true"
      />
      <FormKit
        type="select"
        name="unit_id"
        id="unit_id"
        :options="allUnits"
        label="Parent department/sec/unit"
        validation="number|min:1|max:500"
        validation-visibility="submit"
      />
      <FormKit
        type="date"
        name="start_date"
        id="start_date"
        label="Date Created"
        :min="start_date"
        :max="end_date"
        :value="today"
        :validation="
          'required|date_after_or_equal :' + end_date + '|date_before_or_equal:' + start_date
        "
        validation-visibility="submit"
      />
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
