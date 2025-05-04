<template>
	<div class="mt-4 bg-gray-50 dark:bg-gray-700">
		<nav class="flex space-x-8">
			<a
				v-for="tab in tabs"
				:key="tab.name"
				:href="tab.href"
				:class="[
					tab.current
						? 'border-green-500 text-green-600 dark:text-gray-50 font-semibold'
						: 'border-transparent text-gray-500 dark:text-gray-200 hover:border-gray-300  bg-gray-50 dark:bg-gray-700 hover:text-gray-900 px-4 pt-4',
					'whitespace-nowrap border-b-2 px-4 pt-4 pb-4 text-sm font-medium bg-gray-100 dark:bg-gray-600',
				]"
				:aria-current="tab.current ? 'page' : undefined"
				@click.prevent="emit('tabClicked', tab)"
				>{{ tab.name }}</a
			>
		</nav>
	</div>
</template>

<script setup>
const emit = defineEmits(["tabClicked"]);
const props = defineProps({
	title: {
		type: String,
		default: "",
	},
	tabs: {
		type: Array,
		required: true,
	},
	current: {
		type: Object,
		default: false,
	},
});
</script>
