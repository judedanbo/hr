<script setup>
import { ref } from "vue";
import NewModal from "@/Components/NewModal.vue";
import { ArrowsPointingOutIcon, XMarkIcon } from "@heroicons/vue/24/outline";

defineProps({
	title: { type: String, default: "Chart" },
});

const open = ref(false);
</script>

<template>
	<div class="relative h-full">
		<button
			type="button"
			class="absolute top-2 right-2 z-10 p-1.5 rounded-md bg-white/70 dark:bg-gray-900/70 hover:bg-white dark:hover:bg-gray-900 text-gray-600 dark:text-gray-300 shadow-sm"
			title="Expand chart"
			@click="open = true"
		>
			<ArrowsPointingOutIcon class="h-4 w-4" />
		</button>

		<slot />

		<NewModal :show="open" @close="open = false">
			<div class="relative bg-white dark:bg-gray-800 p-4 sm:p-6 w-[95vw] max-w-6xl h-[85vh]">
				<div class="flex items-center justify-between mb-3">
					<h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ title }}</h3>
					<button
						type="button"
						class="p-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300"
						@click="open = false"
					>
						<XMarkIcon class="h-5 w-5" />
					</button>
				</div>
				<div class="h-[calc(85vh-4rem)]">
					<slot name="expanded">
						<slot />
					</slot>
				</div>
			</div>
		</NewModal>
	</div>
</template>
