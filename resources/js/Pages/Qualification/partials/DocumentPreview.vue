<script setup>
import {
	DocumentArrowUpIcon,
	ChevronLeftIcon,
	ChevronRightIcon,
} from "@heroicons/vue/20/solid";
import { computed } from "vue";

const props = defineProps({
	url: {
		type: String,
		default: null,
	},
	type: {
		type: String,
		default: null,
	},
	title: {
		type: String,
		default: null,
	},
	currentIndex: {
		type: Number,
		default: 0,
	},
	totalCount: {
		type: Number,
		default: 1,
	},
});

const emit = defineEmits(["prev", "next"]);

const showNavigation = computed(() => props.totalCount > 1);
</script>
<template>
	<div class="bg-white dark:bg-gray-700 rounded-lg overflow-hidden">
		<!-- Header with title and navigation -->
		<div
			class="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-gray-600"
		>
			<h3
				class="text-lg font-medium text-gray-900 dark:text-gray-100 truncate"
			>
				{{ title || "Document Preview" }}
			</h3>
			<div
				v-if="showNavigation"
				class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-300"
			>
				<button
					type="button"
					:disabled="currentIndex === 0"
					class="p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-600 disabled:opacity-30 disabled:cursor-not-allowed"
					@click="emit('prev')"
				>
					<ChevronLeftIcon class="w-5 h-5" />
				</button>
				<span>{{ currentIndex + 1 }} of {{ totalCount }}</span>
				<button
					type="button"
					:disabled="currentIndex === totalCount - 1"
					class="p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-600 disabled:opacity-30 disabled:cursor-not-allowed"
					@click="emit('next')"
				>
					<ChevronRightIcon class="w-5 h-5" />
				</button>
			</div>
		</div>

		<!-- Document content -->
		<div class="p-4">
			<template v-if="url">
				<template v-if="type === 'application/pdf'">
					<embed :src="url" :type="type" width="100%" height="500px" />
				</template>
				<template v-else>
					<img
						:src="url"
						alt="preview not available"
						class="mx-auto max-h-[500px] object-contain"
					/>
				</template>
			</template>
			<template v-else>
				<div class="flex flex-col items-center justify-center py-12">
					<DocumentArrowUpIcon
						class="w-16 h-16 text-gray-300 dark:text-gray-500"
					/>
					<p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
						No document available
					</p>
				</div>
			</template>
		</div>
	</div>
</template>
