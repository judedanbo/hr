<script setup>
import { useForm } from '@inertiajs/inertia-vue3';
import { Inertia } from '@inertiajs/inertia'
import {ref} from 'vue'


const step = ref('personalInformation')
const stepNames = [
  {id: 1, name: "Personal", complete: true},
  {id: 2, name: "Contact", complete: false},
  {id: 3, name: "Employment", complete: false},
];

let formData = ref(null)


const submitHandler = (data) => {
  Inertia.post(route('staff.store'), data.staffData);
  console.log(data);
};
</script>
<template>
    <FormKit
      type="form"
      value="formData"
      @submit="submitHandler"
      submit-label="Add Staff"
      #default="{ value }"
      :actions="false"
      :submit-attrs="{
        inputClass:
          'bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline',
        wrapperClass: 'bg-white px-8 pt-4 ',
        ignore: false,
      }"
    >
      <!-- <Staff :steps="stepNames" /> -->
    
      <FormKit type="multi-step" name="staffData" :allow-incomplete="true" tab-style="progress">
          <FormKit type="step" name="personalInformation">
           <div class="md:flex md:gap-2 md:flex-wrap w-full">

             <div class="w-1/4 ">
 
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
                id="date_of_birth"
                value="2011-01-01"
                label="date of birth"
                validation="required|date_after:1923-01-01"
                validation-visibility="submit"
              
              />
  
              <FormKit
                name="gender"
                id="gender"
                type="select"
                label="Gender"
                validation="required"
                :options="{
                  '': 'Select one',
                  M: 'Male',
                  F: 'Female',
                }"
              />
            </div>

            <FormKit
              type="select"
              label="Marital Status"
              name="marital_status"
              validation="required"
              :options="{
                '': 'Select one',
                M: 'Married',
                F: 'Single',
                D: 'Divorced',
                W: 'Widowed',
              }"
             :validation-messages="{
               required: 'Marital status is required',
              }"
            />
          </FormKit>

          <FormKit type="step" name="contactInformation">
            <FormKit
              type="select"
              name="contact_type_id"
              id="contact_type_id"
              label="Contact type"
              placeholder="Contact type"
              validation="required"
              :options="{
                '': 'Select one',
                1: 'Email',
                2: 'Phone',
                3: 'Address',
                4: 'GhanaPOST GPS',
              }"
            />
            <FormKit
              type="text"
              name="contact"
              id="contact"
              label="Contact"
              placeholder="Contact type"
              validation="required|length:2,50"
             
            />
          </FormKit>
          <FormKit type="step" name="employmentInformation">
            <FormKit
                type="date"
                name="hire_date"
                id="hire_date"
                value="2022-01-01"
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
              <FormKit type="submit" label="Add staff"/>
            </template>
          </FormKit>
          {{ $page.props.errors }}
      </FormKit>
    </FormKit>
    
</template>

<!-- 
<style>
.formkit-outer {
  @apply mb-5 w-full
}




.formkit-input {
  @apply  block h-10 rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6
}

.formkit-label{
  @apply block mb-1 font-bold text-sm;
}

::placeholder {
  @apply text-gray-400;
}

.formkit-message{
  @apply text-red-500 text-sm;
}
/* #title{
  @apply w-1/2
} */

</style> -->