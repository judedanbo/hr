<script setup>
import { Inertia } from "@inertiajs/inertia";
const emit = defineEmits(["formSubmitted"]);
import {
  format,
  addDays,
  subYears,
} from "date-fns";

let props = defineProps({
  selectedModel: Object
})

const today = format(new Date(), "yyyy-MM-dd");
const start_date = format(addDays(new Date(), 1), "yyyy-MM-dd");
const end_date = format(subYears(new Date(), 5), "yyyy-MM-dd");

const submitHandler = (data, node) => {
    Inertia.patch(route("institution.update"),
    data, {
        preserveScroll: true,
        onSuccess: () => {
            node.reset()
            emit("formSubmitted");
        },
        onError: (errors) => {
            node.setErrors(['errors'], errors)
        }
    }, 
    props.selectedModel
    )
}

</script>

<template>
  <main class="px-8 py-8 bg-gray-100 dark:bg-gray-700">
    <h1 class="text-2xl pb-4 dark:text-gray-100">Edit Institution</h1>
    <FormKit @submit="submitHandler" type="form" submit-label="Save Institution">
      <div class="sm:flex gap-4 flex-row">
        <div>
          <FormKit
            type="text"
            name="id"
            id="id"
            :value="selectedModel.id"
            validation="required|number|between:2,120"
            label="Institution id"
            placeholder="institution id"
            error-visibility="submit"
            :disabled="true"
          />
        </div>
      <FormKit
        type="text"
        name="name"
        id="name"
        :value="selectedModel.name"
        validation="required|length:2,120"
        label="Institution name"
        placeholder="institution name"
        error-visibility="submit"
        
      />
    </div>
      <div class="sm:flex gap-4">
        <FormKit
          type="text"
          name="abbreviation"
          id="abbreviation"
          :value="selectedModel.abbreviation"
          validation="length:2,6"
          label="Institution abbreviation"
          placeholder="institution's abbreviation"
          error-visibility="submit"
        />
        <FormKit
          type="text"
          name="status"
          id="status"
          :value="selectedModel.status"
          validation="length:2,6"
          label="Status"
          placeholder="status"
          error-visibility="submit"
        />
      </div>
        <div class="sm:flex gap-4">
        <FormKit
          type="date"
          name="start_date"
          id="start_date"
          :value="selectedModel.start_date"
          :min="end_date"
          :max="start_date"
          label="Start date"
          :validation="
            'required|date_after:' + end_date + '|date_before:' + start_date
          "
          validation-visibility="submit"
        />
        <FormKit
          type="date"
          name="end_date"
          id="end_date"
          :value="selectedModel.end_date"
          :min="start_date"
          label="End date"
         
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
