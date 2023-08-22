<script setup>
import { ref } from "vue";

defineProps({
  modelValue: String,
  name: String,
  placeholder: String,
  type: {
    type: String,
    default: "text",
  },
});

defineEmits(["update:modelValue"]);

const input = ref(null);

// onMounted(() => {
//     if (input.value.hasAttribute("autofocus")) {
//         input.value.focus();
//     }
// });
</script>

<template>
  <div>
    <div class="mt-2">
      <input
        @input="$emit('update:modelValue', $event.target.value)"
        :value="modelValue"
        :type="type"
        ref="input"
        class="block w-full rounded-md border-0 py-1.5 `text-gray`-900 dark:text-gray-50 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-100 placeholder:text-gray-400 dark:placeholder:text-gray-100 focus:ring-2 focus:ring-inset focus:ring-green-600 dark:focus:ring-gray-50 sm:text-sm sm:leading-6 dark:bg-gray-700"
        :placeholder="placeholder ?? name"
      />
    </div>
  </div>
</template>
