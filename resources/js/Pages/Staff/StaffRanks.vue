<script setup>
import { format } from "date-fns";

const getDate = (dateString) => {
    return new Date(dateString);
};
const formatDate = (date) => {
    return format(date, "dd MMMM, yyyy");
};
defineProps({
    ranks: Array,
});
</script>

<template>
    <div class="odd:bg-white even:bg-slate-50 px-4 py-5">
        <dt class="text-sm font-medium text-gray-500">Ranks</dt>
        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
            <div
                v-for="rank in ranks"
                class="even:bg-white pl-4 py-2 hover:bg-gray-100"
            >
                {{ rank.name }}
                <div class="text-xs text-gray-400">
                    {{ formatDate(getDate(rank.start_date)) }}
                    -
                    {{ formatDate(getDate(rank.end_date)) }}
                </div>
            </div>
        </dd>
    </div>
</template>
