<script setup>
defineProps({
    name: String,
});
</script>
<template>
    <div
        class="flex justify-center py-16 text-2xl font-bold text-gray-500 tracking-widest"
    >
        No {{ name }}
    </div>
</template>
