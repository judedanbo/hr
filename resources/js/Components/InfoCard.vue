<script setup>
import { Inertia } from "@inertiajs/inertia";
import { Link } from "@inertiajs/inertia-vue3";

let props = defineProps({
    title: String,
    value: { type: Number, default: null },
    link: {
        required: false,
        type: String,
    },
});

let details = (url = props.link) => {
    Inertia.get(props.link);
};
</script>
<template>
    <!-- <div @click="details()"
        class="p-4 transition-shadow border rounded-lg shadow-sm hover:shadow-lg bg-white cursor-pointer w-48"> -->
    <!-- <div class="flex items-start">
            <div class="flex flex-col flex-shrink-0 space-y-2">
                <span class="text-gray-400">{{ title }}</span>
                <span v-if="value" class="text-lg font-semibold">{{
                    value.toLocaleString()
                }}</span>
            </div>
            <div class="relative min-w-0 ml-auto h-14">
                <canvas class="w-20" id="canvasId"></canvas>
            </div>
        </div> -->

    <!-- <div class="flex flex-col bg-gray-400/5 p-8">
        <dt class="text-sm font-semibold leading-6 text-gray-600">{{ title }}</dt>
        <dd class="order-first text-3xl font-semibold tracking-tight text-gray-900">{{ value.toLocaleString() }}</dd>
    </div> -->

    <!-- <div class="bg-white dark:bg-gray-900 py-16 sm:py-8">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <dl class="grid grid-cols-1 gap-x-8 gap-y-16 text-center lg:grid-cols-3"> -->
    <div class="mx-auto flex max-w-xs flex-col gap-y-4">
        <dt class="text-base leading-7 text-gray-600 dark:text-gray-400">{{ title }}</dt>
        <dd class="order-first text-3xl font-semibold tracking-tight text-gray-900 dark:text-gray-50 sm:text-5xl">
            {{
                value.toLocaleString() }}
        </dd>
    </div>
    <!-- </dl>
        </div>
    </div> -->


    <!-- </div> -->
</template>
