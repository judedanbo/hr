<script setup>
import { Link } from "@inertiajs/vue3";
import { ChevronRightIcon, HomeIcon } from "@heroicons/vue/20/solid";

defineProps({
	links: {
		type: Array,
		required: false,
		default: () => [],
	},
});
</script>
<template>
	<nav aria-label="Breadcrumb" class="flex w-full items-center">
		<ol class="flex flex-wrap items-center gap-x-1 text-sm">
			<li>
				<Link
					:href="route('dashboard')"
					class="text-gray-400 hover:text-gray-500"
				>
					<HomeIcon class="size-5 shrink-0" aria-hidden="true" />
					<span class="sr-only">Home</span>
				</Link>
			</li>
			<template v-for="(link, index) in links" :key="index">
				<li v-if="link.name != null" class="flex items-center">
					<ChevronRightIcon
						class="size-5 shrink-0 text-gray-400 mx-1"
						aria-hidden="true"
					/>
					<span
						v-if="links.length - 1 == index"
						aria-current="page"
						class="font-medium text-gray-700 dark:text-gray-200"
					>
						{{ link.name }}
					</span>
					<Link
						v-else
						:href="link.url"
						class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
					>
						{{ link.name }}
					</Link>
				</li>
			</template>
		</ol>
	</nav>
</template>
