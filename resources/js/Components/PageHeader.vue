<script setup>
defineProps({
    name: String
})
</script>
<template>
    <h2 class="font-semibold text-xl text-gray-800 leading-tight dark:text-gray-50">
        {{ name }}
    </h2>
</template>