<script setup>
const props = defineProps({
	initials: {
		type: String,
		default: null,
	},
	image: {
		type: String,
		default: null,
	},
	size: {
		type: String,
		default: "sm",
	},
});
</script>

<template>
	<figure>
		<img
			v-if="image"
			:class="size == 'sm' ? ' w-12 h-12' : ' w-56 h-56'"
			class="flex-none rounded-xl md:rounded-full bg-gray-50 object-cover object-center"
			:src="image"
			:alt="initials"
		/>
		<div
			v-else
			class="w-12 h-12 rounded-full bg-gray-400 dark:bg-gray-100 flex justify-center items-center"
		>
			<h1 class="text-white dark:text-gray-900 font-bold tracking-widest">
				{{ initials }}
			</h1>
		</div>
	</figure>
</template>
