<script setup>
import {
  Dialog,
  DialogPanel,
  DialogTitle,
  TransitionChild,
  TransitionRoot,
} from "@headlessui/vue";
import { PlusCircleIcon } from "@heroicons/vue/24/outline";
import { defineEmits } from "vue";
import { useForm } from "@inertiajs/inertia-vue3";
import { ref } from 'vue'
import Staff from "@/Components/Steps.vue"

defineProps({
  open: Boolean,
});

const emit = defineEmits(["closeDialog"]);

let formData = useForm(null);

const submitForm = () => {
  //   setTimeout(() => {
  //     formData.reset();
  //   }, 3000);
  formData.post(route("staff.store"));
  //   console.log(data);
};

const closeDialog = () => {
  console.log("closeDialog");
  //   emit("closeDialog");
};

const step = ref('personalInformation')
const stepNames = [
  {id: 1, name: "Personal", complete: true},
  {id: 2, name: "Contact", complete: false},
  {id: 3, name: "Employment", complete: false},
];


const camel2title = (str) => str
  .replace(/([A-Z])/g, (match) => ` ${match}`)
  .replace(/^./, (match) => match.toUpperCase())
  .trim()

</script>

<template>
  <TransitionRoot as="template" :show="open">
    <Dialog as="div" class="relative z-10" @click="closeDialog">
      <TransitionChild
        as="template"
        enter="ease-out duration-300"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="ease-in duration-200"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div
          class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
        />
      </TransitionChild>

      <div class="fixed inset-0 z-10 overflow-y-auto">
        <div
          class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0"
        >
          <TransitionChild
            as="template"
            enter="ease-out duration-300"
            enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            enter-to="opacity-100 translate-y-0 sm:scale-100"
            leave="ease-in duration-200"
            leave-from="opacity-100 translate-y-0 sm:scale-100"
            leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          >
            <DialogPanel
              class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6"
            >
              <div class="">
                
                <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                  <DialogTitle
                    as="h3"
                    class="text-base font-semibold leading-6 text-gray-900"
                  >
                    Add New Staff
                  </DialogTitle>
                </div>
              </div>
              <div class="mt-2">
                <FormKit
                  type="form"
                  @submit="submitForm"
                  :value="formData"
                  submit-label="Add Staff"
                  message-class="text-red-500 text-sm"
                  outer-class="basis-full"
                  :submit-attrs="{
                    inputClass:
                      'bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline',
                    wrapperClass: 'bg-white px-8 pt-4 ',
                    ignore: false,
                  }"
                >
                <Staff :steps="stepNames"/>

                  <div class="form-body">
                    <section v-show="step === 'personalInformation'">
                      <FormKit type="group" name="personalInformation">
                        <FormKit
                          type="text"
                          name="title"
                          id="title"
                          label="Title"
                          placeholder="title"
                          help="Enter title of staff"
                          validation-visibility="submit"
                          validation="length:1,10"
                          inner-class="flex-none"
                          message-class="text-red-500 text-sm"
                        />
                        <FormKit
                          type="text"
                          name="first_name"
                          id="first_name"
                          validation="required|length:2,60"
                          label="First name"
                          placeholder="First name"
                          help="Enter surname of new staff"
                          error-visibility="submit"
                          message-class="text-red-500 text-sm"
                        />

                        <FormKit
                          type="text"
                          name="surname"
                          id="surname"
                          validation="required|length:2,60"
                          label="Surname"
                          help="Enter surname of new staff"
                          placeholder="Surname"
                          message-class="text-red-500 text-sm"
                        />
                        <FormKit
                          type="text"
                          name="other_names"
                          id="other_names"
                          label="other Names"
                          help="Enter other names of new staff"
                          placeholder="other names"
                          validation="length:2,100"
                          message-class="text-red-500 text-sm"
                        />

                        <FormKit
                          type="date"
                          name="date_of_birth"
                          id="date_of_birth"
                          value="2011-01-01"
                          label="date of birth"
                          help="Enter your birth day"
                          validation="required|date_after:1923-01-01"
                          validation-visibility="submit"
                          message-class="text-red-500 text-sm"
                        />

                        <FormKit
                          name="gender"
                          id="gender"
                          type="select"
                          label="Gender"
                          :options="{
                            '': 'Select one',
                            M: 'Male',
                            F: 'Female',
                          }"
                        />

                        <FormKit
                          type="select"
                          label="Marital Status"
                          name="marital_status"
                          :options="{
                            '': 'Select one',
                            M: 'Married',
                            F: 'Single',
                            D: 'Divorced',
                            W: 'Widowed',
                          }"
                        />
                      </FormKit>
                    </section>

                    <section v-show="step === 'contactInformation'">
                      <FormKit type="group" name="contactInformation">
                        <FormKit
                          type="select"
                          name="contact_type_id"
                          id="contact_type_id"
                          label="Contact type"
                          help="Enter the type of contact of the new staff"
                          placeholder="Contact type"
                          validation="length:2,5"
                          message-class="text-red-500 text-sm"
                        />
                        <FormKit
                          type="text"
                          name="contact"
                          id="contact"
                          label="Contact"
                          help="Enter actual contact of new staff"
                          placeholder="Contact type"
                          validation="length:2,5"
                          message-class="text-red-500 text-sm"
                        />
                      </FormKit>
                    </section>
                    <section v-show="step === 'employmentInformation'">
                      <FormKit type="group" name="employmentInformation">
                        <FormKit
                          type="text"
                          name="file_number"
                          id="file_number"
                          label="File number"
                          help="Enter File number of new staff"
                          placeholder="File number"
                          validation="length:2,10"
                          message-class="text-red-500 text-sm"
                        />
                        <FormKit
                          type="date"
                          name="date_hired"
                          id="date_hired"
                          value="2011-01-01"
                          label="date of birth"
                          help="Enter your birth day"
                          validation="required|date_after:1923-01-01"
                          validation-visibility="submit"
                          message-class="text-red-500 text-sm"
                        />
                        <FormKit
                          type="textarea"
                          name="remarks"
                          id="remarks"
                          label="Remarks"
                          help="Enter remarks"
                          placeholder="Remarks"
                          validation="length:2,100"
                          message-class="text-red-500 text-sm"
                        />
                      </FormKit>
                    </section>

                    <!-- <details>
                      <summary>Form data</summary>
                      <pre>{{ value }}</pre>
                    </details> -->
                  </div>
                </FormKit>
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<style>

label {
  @apply block mb-1 font-bold text-sm;
}

::placeholder {
  @apply text-gray-400;
}

input {
  @apply block w-full rounded-md border-0 py-1.5 text-gray-900 dark:text-gray-50 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-100 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-green-600 dark:focus:ring-gray-50 sm:text-sm sm:leading-6 dark:bg-gray-700;
}

.formkit-help {
  @apply text-gray-400 text-xs;
}
select {
  @apply w-full;
}

/* .formkit-inner {
  @apply max-w-md border border-gray-400 rounded-lg mb-1 overflow-hidden focus-within:border-green-500;
} */
</style>
