<script setup>
import { Link } from "@inertiajs/inertia-vue3";
import { ChevronRightIcon, HomeIcon } from "@heroicons/vue/20/solid";

defineProps({
	links: {
		type: Array,
		required: false,
		default: [],
	},
});
</script>
<template>
	<nav class="rounded-md w-full flex bg-white dark:bg-gray-700 pl-8">
		<ol class="list-reset flex items-center">
			<li>
				<!-- <a href="/" class="text-green-600 hover:text-green-700"> </a> -->
				<Link href="/dashboard" class="text-gray-400 hover:text-gray-500">
					<HomeIcon class="size-5 shrink-0" aria-hidden="true" />
					<span class="sr-only">Home</span>
				</Link>
			</li>
			<template v-if="links.length > 0">
				<div
					v-for="(link, index) in links"
					:key="index"
					class="flex items-center"
				>
					<div v-if="link.name != null" class="flex items-center">
						<li>
							<span class="text-gray-500 dark:text-gray-50 mx-2">
								<ChevronRightIcon
									class="size-5 shrink-0 text-gray-400"
									aria-hidden="true"
								/>
							</span>
						</li>
						<li
							v-if="links.length - 1 == index"
							class="text-gray-500 dark:text-gray-50"
						>
							{{ link.name }}
						</li>
						<li v-else class="text-green-600 hover:text-green-700">
							<Link :href="link.url">{{ link.name }}</Link>
						</li>
					</div>
				</div>
			</template>
		</ol>
	</nav>
</template>
