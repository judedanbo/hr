<script setup>
const props = defineProps({
	initials: String,
	image: String,
});
</script>

<template>
	<div>
		<!-- <pre>{{ person }}</pre> -->
		<img
			v-if="image"
			class="flex-none w-56 h-56 rounded-full bg-gray-50 object-cover object-center"
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
	</div>
</template>
