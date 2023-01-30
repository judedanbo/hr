<script setup>
import { format } from "date-fns";

const getDate = (dateString) => {
    return new Date(dateString);
};
const formatDate = (date) => {
    return format(date, "dd MMMM, yyyy");
};
defineProps({
    units: Array,
});
</script>

<template>
    <div class="odd:bg-white even:bg-slate-50 px-4 py-5">
        <dt class="text-sm font-medium text-gray-500">Units</dt>
        <dd
            class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0 space-x-y space-y-2"
        >
            <div v-for="unit in units" class="py-2">
                {{ unit.name }}
                <div class="text-xs text-gray-400">
                    {{ formatDate(getDate(unit.start_date)) }}
                    -
                    {{ formatDate(getDate(unit.end_date)) }}
                </div>
            </div>
        </dd>
    </div>
</template>
