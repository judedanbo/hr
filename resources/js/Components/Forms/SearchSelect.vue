<script setup>
import { computed } from 'vue'
import {
    Listbox,
    ListboxButton,
    ListboxOptions,
    ListboxOption,
} from '@headlessui/vue'
import { CheckIcon, ChevronUpDownIcon } from '@heroicons/vue/20/solid'

const props = defineProps({
    modelValue: {
        type: [String, Number],
        default: null,
    },
    options: {
        type: Array,
        required: true,
    },
    placeholder: {
        type: String,
        default: 'Select an option',
    },
    label: {
        type: String,
        default: '',
    },
    error: {
        type: String,
        default: '',
    },
})

const emit = defineEmits(['update:modelValue'])

const selectedOption = computed(() => {
    return props.options.find((option) => option.value === props.modelValue)
})

const updateValue = (option) => {
    emit('update:modelValue', option?.value || null)
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
        <Listbox :model-value="selectedOption" @update:model-value="updateValue">
            <div class="relative">
                <ListboxButton
                    class="relative w-full cursor-pointer rounded-md bg-white dark:bg-gray-700 py-2 pl-3 pr-10 text-left shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 sm:text-sm"
                >
                    <span
                        class="block truncate"
                        :class="
                            selectedOption
                                ? 'text-gray-900 dark:text-gray-100'
                                : 'text-gray-400 dark:text-gray-500'
                        "
                    >
                        {{ selectedOption?.label || placeholder }}
                    </span>
                    <span
                        class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2"
                    >
                        <ChevronUpDownIcon
                            class="h-5 w-5 text-gray-400"
                            aria-hidden="true"
                        />
                    </span>
                </ListboxButton>

                <transition
                    leave-active-class="transition ease-in duration-100"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0"
                >
                    <ListboxOptions
                        class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white dark:bg-gray-700 py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm"
                    >
                        <ListboxOption
                            v-slot="{ active, selected }"
                            :value="null"
                            as="template"
                        >
                            <li
                                :class="[
                                    active
                                        ? 'bg-indigo-600 text-white'
                                        : 'text-gray-900 dark:text-gray-100',
                                    'relative cursor-pointer select-none py-2 pl-3 pr-9',
                                ]"
                            >
                                <span
                                    :class="[
                                        selected ? 'font-semibold' : 'font-normal',
                                        'block truncate',
                                    ]"
                                >
                                    {{ placeholder }}
                                </span>
                                <span
                                    v-if="selected"
                                    :class="[
                                        active ? 'text-white' : 'text-indigo-600',
                                        'absolute inset-y-0 right-0 flex items-center pr-4',
                                    ]"
                                >
                                    <CheckIcon class="h-5 w-5" aria-hidden="true" />
                                </span>
                            </li>
                        </ListboxOption>
                        <ListboxOption
                            v-for="option in options"
                            :key="option.value"
                            v-slot="{ active, selected }"
                            :value="option"
                            as="template"
                        >
                            <li
                                :class="[
                                    active
                                        ? 'bg-indigo-600 text-white'
                                        : 'text-gray-900 dark:text-gray-100',
                                    'relative cursor-pointer select-none py-2 pl-3 pr-9',
                                ]"
                            >
                                <span
                                    :class="[
                                        selected ? 'font-semibold' : 'font-normal',
                                        'block truncate',
                                    ]"
                                >
                                    {{ option.label }}
                                </span>
                                <span
                                    v-if="selected"
                                    :class="[
                                        active ? 'text-white' : 'text-indigo-600',
                                        'absolute inset-y-0 right-0 flex items-center pr-4',
                                    ]"
                                >
                                    <CheckIcon class="h-5 w-5" aria-hidden="true" />
                                </span>
                            </li>
                        </ListboxOption>
                    </ListboxOptions>
                </transition>
            </div>
        </Listbox>
        <p v-if="error" class="mt-1 text-sm text-red-600 dark:text-red-400">
            {{ error }}
        </p>
    </div>
</template>
