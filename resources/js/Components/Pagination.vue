<script setup>
import { ChevronLeftIcon, ChevronRightIcon } from "@heroicons/vue/24/outline";
import { router } from "@inertiajs/vue3";
const emit = defineEmits(["refreshData"]);
const pageClicked = (link = null) => {
	if (link) {
		if (route().current() === "job.show") {
			emit("refreshData", link);
			return;
		}
		router.visit(link, { preserveScroll: true });
	}
};
defineProps({
	navigation: { type: Object, required: true },
});
</script>
<template>
	<footer
		v-if="navigation.total > 0"
		class="bg-white dark:bg-gray-800 px-4 py-3 flex items-center justify-between border-t border-gray-200 dark:border-gray-700 sm:px-6"
	>
		<div class="flex-1 flex justify-between sm:hidden">
			<button
				type="button"
				class="btn btn-secondary btn-sm"
				:disabled="!navigation.prev_page_url"
				@click="pageClicked(navigation.prev_page_url)"
			>
				Previous
			</button>
			<button
				type="button"
				class="btn btn-secondary btn-sm ml-3"
				:disabled="!navigation.next_page_url"
				@click="pageClicked(navigation.next_page_url)"
			>
				Next
			</button>
		</div>
		<div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
			<div>
				<p class="text-sm text-gray-700 dark:text-gray-300">
					Showing
					{{ " " }}
					<span class="font-medium tabular-nums">{{ navigation.from }}</span>
					{{ " " }}
					to
					{{ " " }}
					<span class="font-medium tabular-nums">{{ navigation.to }}</span>
					{{ " " }}
					of
					{{ " " }}
					<span class="font-medium tabular-nums">{{ navigation.total }}</span>
					{{ " " }}
					results
				</p>
			</div>
			<div>
				<nav
					class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px"
					aria-label="Pagination"
				>
					<button
						type="button"
						class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm font-medium text-gray-500 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed"
						:disabled="!navigation.prev_page_url"
						@click="pageClicked(navigation.prev_page_url)"
					>
						<span class="sr-only">Previous</span>
						<ChevronLeftIcon class="h-5 w-5" aria-hidden="true" />
					</button>
					<button
						v-for="(link, index) in navigation.links.slice(1, -1)"
						:key="index"
						type="button"
						class="relative inline-flex items-center px-4 py-2 border text-sm font-medium tabular-nums"
						:class="
							link.active
								? 'z-10 bg-green-50 dark:bg-green-500/10 border-green-600 dark:border-green-500 text-green-700 dark:text-green-300'
								: 'bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'
						"
						:aria-current="link.active ? 'page' : undefined"
						@click="pageClicked(link.url)"
					>
						{{ link.label }}
					</button>
					<button
						type="button"
						class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm font-medium text-gray-500 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed"
						:disabled="!navigation.next_page_url"
						@click="pageClicked(navigation.next_page_url)"
					>
						<span class="sr-only">Next</span>
						<ChevronRightIcon class="h-5 w-5" aria-hidden="true" />
					</button>
				</nav>
			</div>
		</div>
	</footer>
</template>
