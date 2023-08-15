<script setup>
import { ref, onMounted} from 'vue'

const contact_types = ref([]);  
const gender = ref([]);  
const maritalStatus = ref([])
const nationality = ref([])
onMounted(async () => {
  const { data } = await axios.get(route("contact-type.index"));
  contact_types.value = data;
  const  genderData  = await axios.get(route('gender.index'))
  gender.value = genderData.data;
  const maritalStatusData = await axios.get(route('marital-status.index'));
  maritalStatus.value = maritalStatusData.data

  const nationalityData = await axios.get(route('nationality.index'));
  nationality.value = nationalityData.data
});
</script>
<template>
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
    <div class="md:flex-grow">
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
    <div class="md:flex-grow">
      <FormKit
        type="text"
        name="surname"
        id="surname"
        validation="required|length:2,60"
        label="Surname"
        placeholder="Surname"
      />
    </div>
    <div class="md:flex-grow">
      <FormKit
        type="text"
        name="other_names"
        id="other_names"
        label="Other Names"
        placeholder="other names"
        validation="length:2,100"
      />
    </div>
  </div>
  <div class="md:flex justify-between flex-wrap">
    <FormKit
      type="date"
      name="date_of_birth"
      id="date_of_birth"
     
      min="1923-01-01"
      max="2006-01-01"
      label="date of birth"
      validation="required|date_after:1923-01-01|date_before:2005-01-01"
      validation-visibility="submit"
      outer-class="md:w-1/3 lg:w-1/4"
    />

    <FormKit
      name="gender"
      id="gender"
      type="select"
      label="Gender"
      validation="required"
      placeholder="Select one"
      :options="gender"
      outer-class="md:w-1/4 lg:w-1/3"
    />
    <FormKit
      name="nationality"
      id="nationality"
      type="select"
      label="Nationality"
      validation="required"
      placeholder="Select one"
      :options="nationality"
      outer-class="md:w-1/3 lg:w-1/3"
    />
    <FormKit
      type="select"
      label="Marital Status"
      id="marital_status"
      name="marital_status"
      placeholder="Select one"
      validation=""
      :options="maritalStatus"
      outer-class="w-1/3"
    />
    <FormKit
        type="text"
        name="religion"
        id="religion"
        label="Religion"
        placeholder="religion"
        validation="length:2,40"
        outer-class="w-1/3"
      />
  </div>

</template>
