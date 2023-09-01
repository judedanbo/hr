<script setup>
import { Inertia } from "@inertiajs/inertia";
import { ref, onMounted } from "vue";
const emit = defineEmits(["formSubmitted"]);

import { format, addYears, subYears } from "date-fns";

let props = defineProps({
  institutionName: String,
  institutionId: Number,
  unit: {
    type: Object,
    required: true,
  },
});
let unit_types =  ref([]);
let unitList =  ref([]);
onMounted(async () => {
  const unitTypesData = await axios.get(route("unit-type.index"));
  const unitListData = await axios.get(route("institution.unit-list", {institution: props.institutionId}));
  unit_types.value = unitTypesData.data;
  unitList.value = unitListData.data;
});
const start_date = format(subYears(new Date(), 1), "yyyy-MM-dd");
const end_date = format(addYears(new Date(), 1), "yyyy-MM-dd");


const submitHandler = (data, node) => {
  Inertia.patch(route("unit.update", {unit: data.id}), data, {
    preserveScroll: true,
    onSuccess: () => {
      node.reset();
      emit("formSubmitted");
    },
    onError: (errors) => {
      node.setErrors(["errors"], errors);
    },
  });
};
</script>

<template>
  <main class="px-8 py-8 bg-gray-100 dark:bg-gray-700">
    <h1 class="text-2xl pb-4 dark:text-gray-100">
      Edit Department/Section/Unit
    </h1>
    <FormKit @submit="submitHandler" type="form" submit-label="Save">
      <FormKit
        type="hidden"
        name="institution_id"
        id="institution_id"
        :value="unit.institution_id"
      />
      <FormKit
        type="hidden"
        name="id"
        id="id"
        :value="unit.id"
      />
      <FormKit
        type="hidden"
        name="unit_id"
        id="unit_id"
        :value="unit.unit_id"
      />
      <FormKit
        type="text"
        name="name"
        id="name"
        :value="unit.name"
        label="Name of department/sec/unit"
        validation="required|string|length:2,100"
        validation-visibility="submit"
      />
      <FormKit
        type="select"
        name="type"
        id="type"
        label="Unit type"
        placeholder="Select unit type"
        validation="string|length:1,5"
        validation-visibility="submit"
        v-model="unit.type"
      >
        <option
          v-for="unitType in unit_types"
          :id="unitType.value"
          :name="unitType.value"
          :key="unitType.value"
          :value="unitType.value"
        
        >
          {{ unitType.label }}
        </option>
    </FormKit>
      <FormKit
        type="hidden"
        name="institution"
        id="institution"
        :value="institutionName"
        validation="string|length:2,100"
        validation-visibility="submit"
        disabled="true"
      />
      <FormKit
        type="select"
        name="parent"
        id="parent"
        label="Parent department/sec/unit"
        placeholder="Select parent unit"
        validation="number|min:1|max:500"
        validation-visibility="submit"
        v-model="unit.unit_id"
      >
        <option
          v-for="unit in unitList"
          :key="unit.value"
          :value="unit.value"
        >
          {{ unit.label }}
        </option>
    </FormKit>
      <FormKit
        type="date"
        name="start_date"
        id="start_date"
        label="Date Created"
        :min="start_date"
        :max="end_date"
        :value="unit.start_date?.substring(0,10)"
        :validation="
          'date_after_or_equal :' + end_date + '|date_before_or_equal:' + start_date
        "
        validation-visibility="submit"
      />
      <FormKit
        type="date"
        name="end_date"
        id="end_date"
        label="Date removed"
        :min="start_date"
        :max="end_date"
        :value="unit.end_date?.substring(0,10)"
        :validation="
          'date_after_or_equal :' + end_date + '|date_before_or_equal:' + start_date
        "
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
