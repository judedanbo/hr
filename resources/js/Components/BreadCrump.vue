<script setup>
defineProps({
    links: {
        type: Array,
        required: false,
        default: [],
    },
});
</script>
<template>
    <nav class="rounded-md w-full">
        <ol class="list-reset flex">
            <li>
                <a href="/" class="text-sm text-green-600 hover:text-green-700"
                    >Dashboard</a
                >
            </li>
            <template v-if="links.length > 0">
                <div v-for="(link, index) in links" :key="index" class="flex">
                    <li><span class="text-sm text-gray-500 mx-2">/</span></li>
                    <li v-if="links.length - 1 == index" class="text-gray-500">
                        {{ link.name }}
                    </li>
                    <li
                        v-else
                        class="text-sm text-green-600 hover:text-green-700"
                    >
                        <a :href="link.url">{{ link.name }}</a>
                    </li>
                </div>
            </template>
        </ol>
    </nav>
</template>
