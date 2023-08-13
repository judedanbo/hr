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
})
const today = format(new Date(), "yyyy-MM-dd");
const start_date = format(addDays(new Date(), 1), "yyyy-MM-dd");
const end_date = format(subYears(new Date(), 1), "yyyy-MM-dd");

const submitHandler = (data, node) => {

    Inertia.post(route("person.address.create", {person: props.person}),
    data, {
        preserveScroll: true,
        onSuccess: () => {
            node.reset()
            emit("formSubmitted");
        },
        onError: (errors) => {
            node.setErrors(['errors'], errors)
        }
    })
}

</script>

<template>
  <main class="px-8 py-8 bg-gray-100 dark:bg-gray-700">
    <h1 class="text-2xl pb-4 dark:text-gray-100">Add Address</h1>
    <FormKit @submit="submitHandler" type="form" submit-label="Save">
      <FormKit type="hidden" name="person_id" id="person_id" :value="person" />
        <FormKit
        type="text"
        name="address_line_1"
        id="address_line_1"
        label="Address line "
        validation="required|string|length:2,100"
        validation-visibility="submit"
        />
        <FormKit
        type="text"
        name="address_line_2"
        id="address_line_2"
        label="Address line 2"
        validation="string|length:2,100"
        validation-visibility="submit"
        />
        <FormKit
        type="text"
        name="city"
        id="city"
        label="City"
        validation="string|length:2,100"
        validation-visibility="submit"
        />
        <FormKit
        type="text"
        name="region"
        id="region"
        label="region"
        validation="string|length:2,100"
        validation-visibility="submit"
        />
        <FormKit
        type="text"
        name="country"
        id="country"
        label="country"
        validation="string|length:2,100"
        validation-visibility="submit"
        />
        <FormKit
        type="text"
        name="post_code"
        id="post_code"
        label="Post Code"
        validation="string|length:2,100"
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
