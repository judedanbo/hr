<script setup>
import { Inertia } from "@inertiajs/inertia";
const emit = defineEmits(["formSubmitted"]);

defineProps({
  ranks: Array,
  staff: Number,
})

import {
  format,
  addDays,
  subYears,
} from "date-fns";

const today = format(new Date(), "yyyy-MM-dd");
const start_date = format(addDays(new Date(), 1), "yyyy-MM-dd");
const end_date = format(subYears(new Date(), 4), "yyyy-MM-dd");

const submitHandler = (data, node) => {
    // console.log(data)
    Inertia.post(route("staff.promote", { staff: data.staff_id }),
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
    <h1 class="text-2xl pb-4 dark:text-gray-100">Promote Staff</h1>
    <FormKit @submit="submitHandler" type="form" submit-label="Save">
      <FormKit type="hidden" name="staff_id" :value="staff" />
      <FormKit
        type="select"
        name="rank_id"
        id="rank_id"
        validation="required|integer|min:1|max:20"
        label="New Rank"
        :options="ranks"
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
            'required|date_after:' + end_date + '|date_before:' + start_date
          "
          validation-visibility="submit"
          inner-class="w-1/2"
        />
      </div>
      <FormKit
        type="text"
        name="remarks"
        id="remarks"
        label="Remarks"
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
