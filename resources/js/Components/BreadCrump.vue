<script setup>
import { Link } from "@inertiajs/inertia-vue3";

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
		<ol class="list-reset flex items-center">
			<li>
				<a
					href="/"
					class="text-xs md:text-sm lg:text-md text-green-600 hover:text-green-700"
				>
				</a>
			</li>
			<template v-if="links.length > 0">
				<div
					v-for="(link, index) in links"
					:key="index"
					class="flex items-center"
				>
					<div v-if="link.name != null" class="flex items-center">
						<li>
							<span
								class="text-xs md:text-sm lg:text-md text-gray-500 dark:text-gray-50 mx-2"
								>/</span
							>
						</li>
						<li
							v-if="links.length - 1 == index"
							class="text-gray-500 dark:text-gray-50 text-xs md:text-sm lg:text-md"
						>
							{{ link.name }}
						</li>
						<li
							v-else
							class="text-xs md:text-sm lg:text-md text-green-600 hover:text-green-700"
						>
							<Link :href="link.url">{{ link.name }}</Link>
						</li>
					</div>
				</div>
			</template>
		</ol>
	</nav>
</template>
