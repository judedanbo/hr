<script setup>
import { Link } from "@inertiajs/inertia-vue3";

defineProps({
    page: Object
})
</script>

<template>
    <Link :href="page.href" :class="{
            'border-green-500 dark:border-gray-200 text-gray-900 dark:text-gray-50': page.active,
        }"
        class="inline-flex items-center border-b-2 border-transparent px-1 pt-1 text-sm font-medium text-gray-500 hover:border-green-300 dark:hover:border-gray-300 hover:text-gray-700 dark:text-gray-400">
    {{ page.name }}
    </Link>
</template>