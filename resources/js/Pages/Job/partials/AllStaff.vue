<script setup>
defineProps({
    rank: {
        type: Number,
        required: true,
    },
});
</script>
<template>
    All Staff {{ rank }}
</template>