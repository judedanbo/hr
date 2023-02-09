<script setup>
import { Link } from "@inertiajs/inertia-vue3";
import BreezeInput from "@/Components/Input.vue";
import { MagnifyingGlassIcon } from "@heroicons/vue/24/outline";

import NoItem from "@/Components/NoItem.vue";

defineProps({
    type: String,
    subs: Array,
    searchText: String,
});

defineEmits(["update:modelValue"]);
</script>

<template>
    <div
        v-if="type != 'Unit'"
        class="shadow-lg rounded-2xl bg-white dark:bg-gray-700 w-full lg:w-2/5 py-4"
    >
        <p
            class="font-bold text-xl px-8 text-gray-700 dark:text-white tracking-wide"
        >
            <span v-text="type"></span>

            <span
                v-if="subs.length"
                class="text-lg text-gray-500 dark:text-white ml-2"
            >
                ({{ subs.length }})
            </span>
        </p>
        <div v-if="subs.length">
            <div class="mt-2 relative mx-8">
                <div
                    class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
                >
                    <span class="text-gray-500 sm:text-sm">
                        <MagnifyingGlassIcon class="w-4 h-4" />
                    </span>
                </div>
                <BreezeInput
                    :modelValue="searchText"
                    @input="$emit('update:modelValue', $event.target.value)"
                    type="search"
                    class="w-full pl-8 bg-slate-100 border-0"
                    required
                    autofocus
                    :placeholder="
                        type == 'Department'
                            ? 'Search divisions...'
                            : 'Search units...'
                    "
                />
            </div>
            <ul class="px-8 pb-6 max-h-96 overflow-y-auto">
                <li
                    v-for="(subUnit, index) in subs"
                    :key="index"
                    class="flex items-center text-gray-600 dark:text-gray-200 justify-between py-4 px-4 rounded-xl hover:bg-slate-200"
                >
                    <div class="flex items-center justify-start text-lg">
                        <span class="mr-4">
                            {{ index + 1 }}
                        </span>
                        <div class="flex flex-col">
                            <Link
                                :href="
                                    route('unit.show', {
                                        unit: subUnit.id,
                                    })
                                "
                                class=""
                            >
                                {{ subUnit.name }}
                            </Link>
                            <div class="flex justify-start space-x-4">
                                <span
                                    v-if="type == 'Department'"
                                    class="text-sm"
                                >
                                    Units:
                                    {{ subUnit.subs }}
                                </span>
                                <span class="text-sm">
                                    Staff:
                                    {{ subUnit.staff_count }}
                                </span>
                            </div>
                        </div>
                    </div>
                </li>
            </ul>
        </div>
        <NoItem v-else :name="type" />
    </div>
</template>
