<template>
	<div class="border-b border-gray-200">
		<div class="sm:flex sm:items-baseline">
			<div class="mt-4 sm:ml-10 sm:mt-0">
				<nav class="-mb-px flex space-x-8">
					<a
						v-for="tab in tabs"
						:key="tab.name"
						:href="tab.href"
						:class="[
							tab.current
								? 'border-green-500 text-green-600 dark:text-gray-50 font-semibold'
								: 'border-transparent text-gray-500 dark:text-gray-200 hover:border-gray-300 hover:text-gray-700 ',
							'whitespace-nowrap border-b-2 px-1 pb-4 text-sm font-medium',
						]"
						:aria-current="tab.current ? 'page' : undefined"
						@click.prevent="emit('tabClicked', tab)"
						>{{ tab.name }}</a
					>
				</nav>
			</div>
		</div>
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
