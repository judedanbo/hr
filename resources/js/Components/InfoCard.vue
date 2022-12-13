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
    // console.log(url);
};
</script>
<template>
    <div
        @click="details()"
        class="p-4 transition-shadow border rounded-lg shadow-sm hover:shadow-lg bg-white cursor-pointer w-48"
    >
        <div class="flex items-start">
            <div class="flex flex-col flex-shrink-0 space-y-2">
                <span class="text-gray-400">{{ title }}</span>
                <span v-if="value" class="text-lg font-semibold">{{
                    value.toLocaleString()
                }}</span>
            </div>
            <div class="relative min-w-0 ml-auto h-14">
                <canvas class="w-20" id="canvasId"></canvas>
            </div>
        </div>
        <span
            class="inline-block px-2 text-sm text-white bg-green-300 rounded mr-1"
            >25%</span
        >
        <span>from 2021</span>
    </div>
</template>
