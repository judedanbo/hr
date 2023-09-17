<script setup>
import { Inertia } from "@inertiajs/inertia";
import { ref } from "vue";
import PersonalInformationForm from "@/Pages/Person/partials/PersonalInformationForm.vue";
import ImageUpload from "@/Pages/Person/partials/ImageUpload.vue";
import NewDependentForm from "@/Pages/Dependent/partials/NewDependentForm.vue";
import axios from "axios";

const emit = defineEmits(["formSubmitted"]);
defineProps({
  staff_id: {
    type: Number,
    required: true,
  },
});

const page_errors = ref(null);

const submitHandler =  (data, node) => {
  const fd = new FormData()
  // fd.append('image', data.staffData.image.image[0].file)
  // const profileImage = data.staffData.image.image[0].file
  // data.staffData.personalInformation.image = profileImage
  // console.log(data.dependentForm.ima.image[0].file)
  fd.append('title', data.dependentForm.personalInformation.title?? '')
  fd.append('surname', data.dependentForm.personalInformation.surname ?? '')
  fd.append('first_name', data.dependentForm.personalInformation.first_name ?? '')
  fd.append('other_names', data.dependentForm.personalInformation.other_names ?? '')
  fd.append('date_of_birth', data.dependentForm.personalInformation.date_of_birth ?? '')
  fd.append('nationality', data.dependentForm.personalInformation.nationality ?? '')
  fd.append('gender', data.dependentForm.personalInformation.gender ?? '')
  fd.append('marital_status', data.dependentForm.personalInformation.marital_status ?? '')
  fd.append('religion', data.dependentForm.personalInformation.religion ?? '')
  fd.append('staff_id', data.dependentForm.relation.staff_id ?? '')
  fd.append('relation', data.dependentForm.relation.relation ?? '')
  if(data.dependentForm.image.image[0]?.file){
    fd.append('image', data.dependentForm.image.image[0].file)
  }
  // fd.append('image', data.dependentForm.image.image[0]?.file ?? '')

  // console.log(data.dependentForm)
  // console.log(fd)

  // axios.post(route("dependent.store"), fd)
  //   .then(function (response) {
  //     console.log(response);
  //     node.reset();
  //     emit("formSubmitted");
  //   })
  //   .catch(function (error) {
  //     console.log(error);
  //     console.log(error.response.data.errors);
  //     node.setErrors(['there are errors'],
  //     {
  //       errors
  //     }
  //     );
  //   });

  // console.log(data.dependentForm)
  // console.log(addStaffForm);
  // axios.post(route("dependent.store"), data.dependentForm)

  Inertia.post(route("dependent.store"), fd, {
    preserveState: true,
    onSuccess: (message) => {
      node.reset();
      emit("formSubmitted");
      console.log("done " + message);
    },
    onError: (errors) => {
      page_errors.value = errors;
      node.setErrors(['there are errors submitting the form'], errors );
    },
  });
};
</script>
<template>
  <main class="bg-gray-100 dark:bg-gray-700 px-8 py-8">
    <h1 class="text-2xl dark:text-gray-200">Add new Dependent</h1>
    <FormKit
      type="form"
      id="addDependentForm"
      name="addDependentForm"
      @submit="submitHandler"
      :actions="false"
      wrapper-class="mx-auto"
    >
      <!-- <Staff :steps="staff" /> -->

      <FormKit
        type="multi-step"
        id="dependentForm"
        name="dependentForm"
        :allow-incomplete="true"
        tab-style="progress"
      >
        <FormKit type="step" id="personalInformation" name="personalInformation">
          <PersonalInformationForm />
        </FormKit>

        <FormKit type="step" id="image" name="image">
          <ImageUpload />
        </FormKit>
        <FormKit type="step" id="relation" name="relation">
          <NewDependentForm :staff_id="staff_id" />
          <template #stepNext>
            <FormKit type="submit" label="Add Dependent" />
          </template>
        </FormKit>
      </FormKit>
    </FormKit>
    <p v-for="error in page_errors" class="text-xs text-rose-500">{{error}}</p>
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
  /* @apply border-0 shadow-none; */;
}
.formkit-outer[data-type="multi-step"]
  > [data-tab-style="progress"]
  > .formkit-steps {
  @apply border-0 shadow-none;
}
.formkit-outer[data-type="multi-step"] > .formkit-wrapper {
  @apply max-w-full;
}
.formkit-tab-label {
  @apply dark:text-gray-200;
}
</style>
