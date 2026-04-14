<script setup>
const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    label: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: '',
    },
    error: {
        type: String,
        default: '',
    },
    min: {
        type: String,
        default: '',
    },
    max: {
        type: String,
        default: '',
    },
})

const emit = defineEmits(['update:modelValue'])

const updateValue = (event) => {
    emit('update:modelValue', event.target.value)
}
</script>

<template>
    <div class="w-full">
        <label
            v-if="label"
            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
        >
            {{ label }}
        </label>
        <input
            type="date"
            :value="modelValue"
            :min="min"
            :max="max"
            :placeholder="placeholder"
            class="block w-full rounded-md border-0 py-1.5 text-gray-900 dark:text-gray-100 bg-white dark:bg-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
            @input="updateValue"
        />
        <p v-if="error" class="mt-1 text-sm text-red-600 dark:text-red-400">
            {{ error }}
        </p>
    </div>
</template>
