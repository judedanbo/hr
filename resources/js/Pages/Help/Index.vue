<script setup>
import { ref, computed, watch, onMounted } from "vue";
import { Head } from "@inertiajs/vue3";
import NewAuthenticated from "@/Layouts/NewAuthenticated.vue";
import BreadCrump from "@/Components/BreadCrump.vue";
import { MagnifyingGlassIcon, XMarkIcon } from "@heroicons/vue/24/outline";

const props = defineProps({
    sections: {
        type: Array,
        required: true,
    },
});

const breadcrumbs = [{ name: "Help", url: route("help.index") }];

// --- State ---
const searchQuery = ref("");
const activeSlug = ref(props.sections[0]?.slug ?? "");
let debounceTimer = null;
const debouncedQuery = ref("");

// --- Plain text cache for search ---
const sectionPlainText = computed(() =>
    props.sections.map((s) => ({
        slug: s.slug,
        text: s.html.replace(/<[^>]*>/g, " ").toLowerCase(),
        title: s.title.toLowerCase(),
    }))
);

// --- Search debounce ---
watch(searchQuery, (val) => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        debouncedQuery.value = val.trim();
    }, 300);
});

// --- Filtered sections ---
const filteredSections = computed(() => {
    const q = debouncedQuery.value.toLowerCase();
    if (!q) return props.sections;

    return props.sections.filter((s) => {
        const pt = sectionPlainText.value.find((p) => p.slug === s.slug);
        return pt && (pt.title.includes(q) || pt.text.includes(q));
    });
});

// --- Match counts per section ---
const matchCounts = computed(() => {
    const q = debouncedQuery.value.toLowerCase();
    if (!q) return {};

    const counts = {};
    sectionPlainText.value.forEach((pt) => {
        let count = 0;
        let idx = 0;
        while ((idx = pt.text.indexOf(q, idx)) !== -1) {
            count++;
            idx += q.length;
        }
        if (pt.title.includes(q)) count++;
        counts[pt.slug] = count;
    });
    return counts;
});

// --- Active section with highlighted content ---
const activeSection = computed(() => {
    return props.sections.find((s) => s.slug === activeSlug.value) ?? props.sections[0];
});

const displayHtml = computed(() => {
    if (!activeSection.value) return "";
    const html = activeSection.value.html;
    const q = debouncedQuery.value.trim();
    if (!q) return html;

    // Highlight search terms in text nodes only (not inside HTML tags)
    const escaped = q.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
    const regex = new RegExp(`(>)([^<]*?)(${escaped})([^<]*?)(<)`, "gi");

    let result = html;
    // Multiple passes to catch overlapping matches in same text node
    for (let i = 0; i < 3; i++) {
        result = result.replace(regex, (match, open, before, term, after, close) => {
            return `${open}${before}<mark class="bg-yellow-200 dark:bg-yellow-700 rounded px-0.5">${term}</mark>${after}${close}`;
        });
    }
    return result;
});

// --- Auto-switch tab when filtered out ---
watch(filteredSections, (filtered) => {
    if (filtered.length > 0 && !filtered.find((s) => s.slug === activeSlug.value)) {
        activeSlug.value = filtered[0].slug;
    }
});

// --- URL hash sync ---
function updateHash() {
    window.location.hash = activeSlug.value;
}

watch(activeSlug, updateHash);

onMounted(() => {
    const hash = window.location.hash.slice(1);
    if (hash && props.sections.find((s) => s.slug === hash)) {
        activeSlug.value = hash;
    }

    window.addEventListener("hashchange", () => {
        const h = window.location.hash.slice(1);
        if (h && props.sections.find((s) => s.slug === h)) {
            activeSlug.value = h;
        }
    });
});

function selectTab(slug) {
    activeSlug.value = slug;
    searchQuery.value = "";
    debouncedQuery.value = "";
}

function clearSearch() {
    searchQuery.value = "";
    debouncedQuery.value = "";
}
</script>

<template>
    <Head title="Help & Documentation" />

    <NewAuthenticated>
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <BreadCrump :links="breadcrumbs" />
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-50 my-4">
                        Help & Documentation
                    </h1>
                </div>
            </div>

            <!-- Search Box -->
            <div class="mt-4 relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <MagnifyingGlassIcon class="h-5 w-5 text-gray-400" />
                </div>
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Search help topics..."
                    class="block w-full pl-10 pr-10 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:ring-2 focus:ring-green-500 focus:border-green-500 sm:text-sm"
                />
                <button
                    v-if="searchQuery"
                    @click="clearSearch"
                    class="absolute inset-y-0 right-0 pr-3 flex items-center"
                >
                    <XMarkIcon class="h-5 w-5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" />
                </button>
            </div>

            <!-- Tab Bar -->
            <div class="mt-4 overflow-x-auto border-b border-gray-200 dark:border-gray-700">
                <nav class="flex gap-1 min-w-max px-1" aria-label="Help topics">
                    <button
                        v-for="section in filteredSections"
                        :key="section.slug"
                        @click="selectTab(section.slug)"
                        :class="[
                            'whitespace-nowrap px-4 py-2.5 text-sm font-medium rounded-t-lg transition-colors',
                            activeSlug === section.slug
                                ? 'bg-white dark:bg-gray-800 text-green-600 dark:text-green-400 border border-b-0 border-gray-200 dark:border-gray-700 -mb-px'
                                : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50',
                        ]"
                    >
                        {{ section.title }}
                        <span
                            v-if="debouncedQuery && matchCounts[section.slug]"
                            class="ml-1.5 inline-flex items-center rounded-full bg-green-100 dark:bg-green-900 px-2 py-0.5 text-xs font-medium text-green-700 dark:text-green-300"
                        >
                            {{ matchCounts[section.slug] }}
                        </span>
                    </button>
                </nav>
            </div>

            <!-- No results -->
            <div
                v-if="filteredSections.length === 0"
                class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-b-lg p-12 text-center"
            >
                <p class="text-gray-500 dark:text-gray-400 text-lg">
                    No help topics match "<strong>{{ debouncedQuery }}</strong>"
                </p>
                <button
                    @click="clearSearch"
                    class="mt-4 text-green-600 dark:text-green-400 hover:underline text-sm"
                >
                    Clear search
                </button>
            </div>

            <!-- Content Area -->
            <div
                v-else
                class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-b-lg"
            >
                <div class="p-6">
                    <article
                        class="prose prose-lg dark:prose-invert max-w-none prose-headings:text-gray-900 dark:prose-headings:text-gray-100 prose-a:text-green-600 dark:prose-a:text-green-400 prose-code:text-green-600 dark:prose-code:text-green-400"
                        v-html="displayHtml"
                    ></article>
                </div>
            </div>

            <!-- Footer -->
            <div class="mt-4 mb-8 text-center text-sm text-gray-400 dark:text-gray-500">
                <p>Last Updated: April 2026 &middot; HR Management System &middot; Version 2026.04</p>
            </div>
        </div>
    </NewAuthenticated>
</template>
