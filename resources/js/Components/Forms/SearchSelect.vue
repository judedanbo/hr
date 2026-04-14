<script setup>
import { computed, ref } from 'vue'
import {
    Listbox,
    ListboxButton,
    ListboxOptions,
    ListboxOption,
} from '@headlessui/vue'
import { CheckIcon, ChevronUpDownIcon, MagnifyingGlassIcon } from '@heroicons/vue/20/solid'

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
    searchable: {
        type: Boolean,
        default: false,
    },
})

const emit = defineEmits(['update:modelValue'])

const searchQuery = ref('')

const selectedOption = computed(() => {
    return props.options.find((option) => option.value === props.modelValue)
})

const filteredOptions = computed(() => {
    if (!props.searchable || !searchQuery.value) {
        return props.options
    }

    const query = searchQuery.value.toLowerCase()
    return props.options.filter((option) => {
        return option.label.toLowerCase().includes(query) ||
               option.short_name?.toLowerCase().includes(query) ||
               option.category?.toLowerCase().includes(query) ||
               option.department?.toLowerCase().includes(query)
    })
})

const updateValue = (option) => {
    emit('update:modelValue', option?.value || null)
    searchQuery.value = ''
}

const clearSearch = () => {
    searchQuery.value = ''
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
                        <!-- Search Input (if searchable) -->
                        <div
                            v-if="searchable"
                            class="sticky top-0 z-20 bg-white/95 dark:bg-gray-700/95 backdrop-blur-sm p-2 border-b border-gray-200 dark:border-gray-600 shadow-sm"
                        >
                            <div class="relative">
                                <MagnifyingGlassIcon
                                    class="absolute left-2 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400 z-10"
                                    aria-hidden="true"
                                />
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    class="w-full pl-8 pr-3 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                    placeholder="Search..."
                                    @click.stop
                                    @keydown.stop
                                />
                            </div>
                        </div>

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
                            v-for="option in filteredOptions"
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

                        <!-- No results message -->
                        <li
                            v-if="searchable && searchQuery && filteredOptions.length === 0"
                            class="relative cursor-default select-none py-2 pl-3 pr-9 text-gray-500 dark:text-gray-400"
                        >
                            No results found
                        </li>
                    </ListboxOptions>
                </transition>
            </div>
        </Listbox>
        <p v-if="error" class="mt-1 text-sm text-red-600 dark:text-red-400">
            {{ error }}
        </p>
    </div>
</template>
