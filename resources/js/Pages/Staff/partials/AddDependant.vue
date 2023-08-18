<script setup>
import { Inertia } from "@inertiajs/inertia";
import PersonalInformationForm from '@/Pages/Person/partials/PersonalInformationForm.vue'
import ImageUpload from '@/Pages/Person/partials/ImageUpload.vue'
import NewDependentForm from '@/Pages/Dependent/partials/NewDependentForm.vue'
import axios from "axios";

const emit = defineEmits(["formSubmitted"]);
defineProps({
  staff_id: {
    type: Number,
    required: true,
  },
});


const submitHandler = async (data, node) => {
  // console.log(data.staffData.personalInformation)
  // const fd = new FormData()  
  const profileImage = data.staffData.image.image[0].file
  // console.log(data.staffData.personalInformation)
  data.staffData.personalInformation.image = profileImage
  // fd.append('image', profileImage)


  // console.log(data.staffData.personalInformation)
  // const person = await axios.post(route('person.store'), data.staffData.personalInformation)

  // console.log(person)
  Inertia.post(route("person.store"), data.staffData.personalInformation, {
    preserveState: true,
    onSuccess: (message) => {
      console.log('Success');
      console.log(message);
      node.reset();
      emit("formSubmitted");
    },
    onError: (errors) => {
      node.setErrors(['there are errors'],
      { 
        errors
        //'staffData.personalInformation.contact': 'contact required',
      }
      );
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
      @submit="submitHandler"
      submit-label="Add Staff"
      #default="{ value }"
      :actions="false"
      wrapper-class="mx-auto"
    >
      <!-- <Staff :steps="staff" /> -->
  
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
          <NewDependentForm :staff_id="staff_id"/>
          <template #stepNext>
            <FormKit type="submit" label="Add Dependent" />
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
