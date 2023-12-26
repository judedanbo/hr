<script setup>
import { ChevronLeftIcon, ChevronRightIcon } from "@heroicons/vue/24/outline";
import { Link } from "@inertiajs/inertia-vue3";
defineProps({
	navigation: { type: Object, required: true },
});
</script>
<template>
	<footer
		v-if="navigation.total > 0"
		class="bg-white dark:bg-gray-800 px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6"
	>
		<div class="flex-1 flex justify-between sm:hidden">
			<Link
				:href="navigation.prev_page_url"
				preserve-scroll
				class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
			>
				Previous
			</Link>
			<Link
				:href="navigation.next_page_url"
				preserve-scroll
				class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
			>
				Next
			</Link>
		</div>
		<div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
			<div>
				<p class="text-sm text-gray-700 dark:text-gray-50">
					Showing
					{{ " " }}
					<span class="font-medium">{{ navigation.from }}</span>
					{{ " " }}
					to
					{{ " " }}
					<span class="font-medium">{{ navigation.to }}</span>
					{{ " " }}
					of
					{{ " " }}
					<span class="font-medium">{{ navigation.total }}</span>
					{{ " " }}
					results
				</p>
			</div>
			<div>
				<nav
					class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px"
					aria-label="Pagination"
				>
					<Link
						:href="navigation.prev_page_url"
						preserve-scroll
						class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-500 dark:text-gray-50 hover:bg-gray-50"
					>
						<span class="sr-only">Previous</span>
						<ChevronLeftIcon class="h-5 w-5" aria-hidden="true" />
					</Link>
					<Link
						v-for="(link, index) in navigation.links.slice(1, -1)"
						:key="index"
						:href="link.url"
						preserve-scroll
						class="z-10 relative inline-flex items-center px-4 py-2 border text-sm font-medium"
						:class="
							link.active
								? 'bg-green-100 dark:bg-gray-800 border-green-500 dark:border-gray-400 text-green-600 dark:text-gray-50'
								: 'bg-white dark:bg-gray-500  border-gray-300 dark:border-gray-600 text-gray-500 dark:text-gray-300'
						"
					>
						{{ link.label }}
					</Link>
					<Link
						:href="navigation.next_page_url"
						preserve-scroll
						class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-500 dark:text-gray-50 hover:bg-gray-50"
					>
						<span class="sr-only">Next</span>
						<ChevronRightIcon class="h-5 w-5" aria-hidden="true" />
					</Link>
				</nav>
			</div>
		</div>
	</footer>
</template>
