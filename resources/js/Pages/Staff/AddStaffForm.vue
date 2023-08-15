<script setup>
import { useForm } from "@inertiajs/inertia-vue3";
import { Inertia } from "@inertiajs/inertia";
import { ref, onMounted } from "vue";
import axios from "axios";
const emit = defineEmits(["formSubmitted"]);

const contact_types = ref([]);  
const gender = ref([]);  
const maritalStatus = ref([])
onMounted(async () => {
  const { data } = await axios.get(route("contact-type.index"));
  contact_types.value = data;
  const  genderData  = await axios.get(route('gender.index'))
  gender.value = genderData.data;
  const maritalStatusData = await axios.get(route('marital-status.index'));
  maritalStatus.value = maritalStatusData.data
});

// onMounted(async() =>{
//   const {gender} = await axios.get(route('gender.index'))
//   console.log(gender);
// })

const step = ref("personalInformation");
const stepNames = [
  { id: 1, name: "Personal", complete: true },
  { id: 2, name: "Contact", complete: false },
  { id: 3, name: "Employment", complete: false },
];

let formData = ref(null);

const submitHandler = (data, node) => {
  Inertia.post(route("staff.store"), data.staffData, {
    preserveState: true,
    onSuccess: () => {
      node.reset();
      emit("formSubmitted");
    },
    onError: (errors) => {
      node.setErrors(['there are errors'],
      { 
        'staffData.contactInformation.contact': 'contact required',
        "staffData.employmentInformation.staff_number": "staff number required",
      }
      );
      errors.forEach(element => {
        
      });
    },
  });
};
</script>
<template>
  <main class="bg-gray-100 dark:bg-gray-700 px-8 py-8">
    <h1 class="text-2xl dark:text-gray-200">Add new Staff</h1>
    <FormKit
      type="form"
      name="addStaffForm"
      id="addStaffForm"
      value="formData"
      @submit="submitHandler"
      submit-label="Add Staff"
      #default="{ value }"
      :actions="false"
      wrapper-class="mx-auto"
    >
      <!-- <Staff :steps="stepNames" /> -->
  
      <FormKit
        type="multi-step"
        name="staffData"
        :allow-incomplete="true"
        tab-style="progress"
      >
        <FormKit type="step" name="personalInformation">
          <div class="md:flex md:gap-2 md:flex-wrap w-full">
            <div class="w-1/4">
              <FormKit
                type="text"
                name="title"
                id="title"
                label="Title"
                placeholder="title"
                validation-visibility="submit"
                validation="length:1,10"
                input-class="w-full"
              />
            </div>
            <div class="flex-grow">
              <FormKit
                type="text"
                name="first_name"
                id="first_name"
                validation="required|length:2,60"
                label="First name"
                placeholder="First name"
                error-visibility="submit"
              />
            </div>
            <div class="w-1/2">
              <FormKit
                type="text"
                name="surname"
                id="surname"
                validation="required|length:2,60"
                label="Surname"
                placeholder="Surname"
              />
            </div>
            <div class="flex-grow">
              <FormKit
                type="text"
                name="other_names"
                id="other_names"
                label="other Names"
                placeholder="other names"
                validation="length:2,100"
              />
            </div>
          </div>
          <div class="md:flex md:gap-2">
            <FormKit
              type="date"
              name="date_of_birth"
              id="Date_of_birth"
              value="2005-01-01"
              min="1923-01-01"
              max="2006-01-01"
              label="date of birth"
              validation="required|date_after:1923-01-01|date_before:2005-01-01"
              validation-visibility="submit"
            />
  
            <FormKit
              name="gender"
              id="gender"
              type="select"
              label="Gender"
              validation="required"
              placeholder="Select one"
              :options="gender"
            />
          </div>
  
          <FormKit
            type="select"
            label="Marital Status"
            id="marital_status"
            name="marital_status"
            placeholder="Select one"
            validation="required"
            :options="maritalStatus"
            :validation-messages="{
              required: 'Marital status is required',
            }"
          />
        </FormKit>
  
        <FormKit type="step" name="contactInformation">
          <FormKit
            type="select"
            name="contact_type"
            id="contact_type"
            label="Contact type"
            placeholder="Select one"
            validation="required"
            :options="contact_types"
          />
          <FormKit
            type="text"
            name="contact"
            id="contact"
            label="Contact"
            placeholder="Contact"
            validation="required|length:2,50"
          />
        </FormKit>
        <FormKit type="step" name="employmentInformation">
          <FormKit
            type="date"
            name="hire_date"
            id="hire_date"
            value="2022-01-01"
            min="2021-01-01"
            max="2022-01-01"
            label="Date of Employment"
            validation="required|date_after:2021-01-01"
            validation-visibility="submit"
          />
  
          <FormKit
            type="text"
            name="file_number"
            id="file_number"
            label="File number"
            placeholder="File number"
            validation="required|length:2,10"
          />
  
          <FormKit
            type="text"
            name="staff_number"
            id="staff_number"
            label="Staff employment number"
            placeholder="Staff number"
            validation="required|length:2,10"
          />
  
          <FormKit
            type="textarea"
            name="remarks"
            id="remarks"
            label="Remarks"
            placeholder="Remarks"
            validation="length:2,200"
          />
          <template #stepNext>
            <FormKit type="submit" label="Add staff" />
          </template>
        </FormKit>
      </FormKit>
    </FormKit>
  </main>
</template>

<style>
.formkit-form {
  /* @apply mx-8 mb-4; */
}
.formkit-wrapper {
  @apply mx-auto;
}
.formkit-step {
  @apply border-0 shadow-none;
}
.formkit-outer[data-type="multi-step"]
  > [data-tab-style="progress"]
  > .formkit-steps {
  @apply border-0 shadow-none;
}
.formkit-tab-label{
  @apply dark:text-gray-200
}
</style>
