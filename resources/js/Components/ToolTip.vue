<script setup>
const emit = defineEmits(["preview", "delete"]);
defineProps({
	tooltip: {
		type: String,
		default: "",
	},
	options: {
		type: Boolean,
		default: false,
	},
});
</script>
<template>
	<div class="relative inline-block group">
		<a to="" class="hover:text-gray-400 font-medium dark:text-gray-100">
			<slot />
		</a>
		<div
			class="flex flex-col p-4 bg-white dark:bg-gray-700 min-w-60 max-w-96 rounded-md z-20 absolute right-0 top-0 invisible group-hover:visible "
		>
			<p href="" class="font-semibold pb-4 dark:text-gray-100 ">{{ tooltip }}</p>
			<ul v-if="options" class="list-disc space-y-2">
				<li class="flex items-start">
					<p
						href=""
						class="font-medium text-sm text-gray-500 hover:text-black cursor-pointer"
						@click="emit('preview')"
					>
						Preview
					</p>
				</li>
				<li class="flex items-start">
					<p
						href=""
						class="font-medium text-sm text-gray-500 hover:text-black cursor-pointer"
						@click="emit('delete')"
					>
						Delete
					</p>
				</li>
			</ul>
		</div>
	</div>
</template>