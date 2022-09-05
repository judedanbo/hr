<script setup>
import { Link } from "@inertiajs/inertia-vue3";

defineProps({
    title: String,
    value: Number,
});

let details = () => {
    Inertia.get(route("institution.staff", { institution: 21 }));
};
</script>
<template>
    <div
        @click="details"
        class="p-4 transition-shadow border rounded-lg shadow-sm hover:shadow-lg bg-white cursor-pointer"
    >
        <div class="flex items-start">
            <div class="flex flex-col flex-shrink-0 space-y-2">
                <span class="text-gray-400">{{ title }}</span>
                <span class="text-lg font-semibold">{{
                    value.toLocaleString()
                }}</span>
            </div>
            <div class="relative min-w-0 ml-auto h-14">
                <canvas id="canvasId"></canvas>
            </div>
        </div>
        <span
            class="inline-block px-2 text-sm text-white bg-green-300 rounded mr-1"
            >25%</span
        >
        <span>from 2021</span>
    </div>
</template>
