<script setup>
import { onMounted, ref } from "vue";

defineProps(
    {
        modelValue: String,
        name: String,
        placeholder: String,
        type: {
            type: String,
            default: "text",
        },
    },

);

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
        <label for="email" class="block text-sm font-medium leading-6 text-gray-900">{{ name }}</label>
        <div class="mt-2">
            <input @input="$emit('update:modelValue', $event.target.value)" :value="modelValue" :type="type" ref="input"
                class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-green-600 sm:text-sm sm:leading-6"
                :placeholder="placeholder ?? name" />
        </div>
    </div>
</template>
