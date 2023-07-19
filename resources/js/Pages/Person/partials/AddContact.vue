<script setup>
import { Inertia } from "@inertiajs/inertia";
const emit = defineEmits(["formSubmitted"]);


import {
    format,
    addDays,
    subYears,
} from "date-fns";

let props = defineProps({
  qualifications: Array,
  person: Number,
  contact_types: Array,
})
const today = format(new Date(), "yyyy-MM-dd");
const start_date = format(addDays(new Date(), 1), "yyyy-MM-dd");
const end_date = format(subYears(new Date(), 1), "yyyy-MM-dd");

const submitHandler = (data, node) => {
    // console.log(data)
    Inertia.post(route("person.contact.create", {person: props.person}),
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
    <h1 class="text-2xl pb-4 dark:text-gray-100">Add Contacts</h1>
    <FormKit @submit="submitHandler" type="form" submit-label="Save">
      <FormKit type="hidden" name="person_id" id="person_id" :value="props.person" />
      <div class="flex justify-center items-center gap-4 flex-wrap md:flex-nowrap w-full">
        <div class="flex-1">

          <FormKit
            type="select"
            name="contact_type"
            id="contact_type"
            label="Contact Type"
            :options="contact_types"
            validation="required|integer|min:1|max:6"
            validation-visibility="submit"
            />
        </div>
        <div class="flex-1">

          <FormKit
          type="text"
          name="contact"
          id="contact"
          label="Contact"
          validation="string|length:2,100"
          validation-visibility="submit"
          />
        </div>
        
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
