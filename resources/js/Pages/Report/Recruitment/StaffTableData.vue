<script setup>
defineProps({
    name: String,
});
</script>
<template>
    <td class="pl-5">
        <slot />
    </td>
</template>
