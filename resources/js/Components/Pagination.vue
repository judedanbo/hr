<!-- This example requires Tailwind CSS v2.0+ -->
<template>
    <div v-if="records.total > 0"
        class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
        <div class="flex-1 flex justify-between sm:hidden">
            <Link :href="records.prev_page_url" preserve-scroll
                class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
            Previous
            </Link>
            <Link :href="records.next_page_url" preserve-scroll
                class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
            Next
            </Link>
        </div>
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
                <p class="text-sm text-gray-700">
                    Showing
                    {{ " " }}
                    <span class="font-medium">{{ records.from }}</span>
                    {{ " " }}
                    to
                    {{ " " }}
                    <span class="font-medium">{{ records.to }}</span>
                    {{ " " }}
                    of
                    {{ " " }}
                    <span class="font-medium">{{ records.total }}</span>
                    {{ " " }}
                    results
                </p>
            </div>
            <div>
                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                    <Link :href="records.prev_page_url" preserve-scroll
                        class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                    <span class="sr-only">Previous</span>
                    <ChevronLeftIcon class="h-5 w-5" aria-hidden="true" />
                    </Link>
                    <!-- Current: "z-10 bg-indigo-50 border-indigo-500 text-indigo-600", Default: "bg-white border-gray-300 text-gray-500 hover:bg-gray-50" -->
                    <Link :href="link.url" v-for="(link, index) in links.slice(1, -1)" :key="index"
                        class="z-10 relative inline-flex items-center px-4 py-2 border text-sm font-medium" :class="link.active
                            ? 'bg-green-50 border-green-500 text-green-600'
                            : 'bg-white border-gray-300 text-gray-500'
                            ">
                    {{ link.label }}
                    </Link>
                    <Link :href="records.next_page_url" preserve-scroll
                        class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                    <span class="sr-only">Next</span>
                    <ChevronRightIcon class="h-5 w-5" aria-hidden="true" />
                    </Link>
                </nav>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ChevronLeftIcon, ChevronRightIcon } from "@heroicons/vue/24/outline";
import { Link } from "@inertiajs/inertia-vue3";
let props = defineProps({
    records: Object,
    // next: String,
    // prev: String,
});

let links = props.records.links.map((link) => {
    if (link.url) {
        link.url = link.url.replace(/^http?:/, 'https:');
    }
    return link
});

</script>
