<script setup>
defineProps({
    name: String,
});
</script>
<template>
    <div
        class="flex justify-center p-8 text-xl font-bold text-gray-500 tracking-widest"
    >
        No {{ name }}
    </div>
</template>
