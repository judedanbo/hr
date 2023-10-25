<script setup>
import { Link } from "@inertiajs/inertia-vue3";
defineProps({
	text: String,
	href: String,
});
</script>
<template>
	<Link
		v-if="href"
		:href="href"
		class="px-2 py-4 bg-green-600 rounded w-36 flex items-center justify-center text-white tracking-wider cursor-pointer"
	>
		{{ text }}
	</Link>
	<div
		v-else
		class="px-2 py-4 bg-green-600 rounded w-36 flex items-center justify-center text-white tracking-wider cursor-pointer"
	>
		{{ text }}
	</div>
</template>
