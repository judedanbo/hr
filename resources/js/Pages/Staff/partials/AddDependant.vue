<script setup>
import { Inertia } from "@inertiajs/inertia";
import PersonalInformationForm from '@/Pages/Person/partials/PersonalInformationForm.vue'
import ImageUpload from '@/Pages/Person/partials/ImageUpload.vue'

const emit = defineEmits(["formSubmitted"]);


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
    <h1 class="text-2xl dark:text-gray-200">Add new Dependent</h1>
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
          <PersonalInformationForm />   
        </FormKit>
  
        <FormKit type="step" name="image">
          
         <ImageUpload/>
        </FormKit>
        <FormKit type="step" name="relation">
          <!-- <FormKit
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
          /> -->
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
    @apply max-w-full
  /* @apply border-0 shadow-none; */
}
.formkit-outer[data-type="multi-step"]
> [data-tab-style="progress"]
> .formkit-steps {
    @apply border-0 shadow-none;
}
.formkit-outer[data-type="multi-step"] > .formkit-wrapper {
    @apply max-w-full
}
.formkit-tab-label{
  @apply dark:text-gray-200
}
</style>
