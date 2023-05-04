<script setup>
import { Link } from "@inertiajs/inertia-vue3";

defineProps({
    page: Object
})
</script>

<template>
    <Link :href="page.href" :class="{
            'border-l-4 border-green-500 bg-green-50 text-green-700 dark:text-gray-50 dark:border-gray-500  dark:bg-gray-600': page.active,
        }" class="block py-2 pl-3 pr-4 text-base font-medium dark:text-gray-400">
    {{ page.name }}
    </Link>
</template>