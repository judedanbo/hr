<script setup>
const emit = defineEmits(["preview", "delete"]);
defineProps({
	tooltip: {
		type: String,
		default: "",
	},
	text: {
		type: String,
		default: "",
	},
});
</script>
<template>
	<div class="relative inline-block tooltip">
		<a to="" class="hover:text-gray-400 px-2 py-1 font-medium">
			<slot />
		</a>
		<div
			class="flex flex-col p-4 bg-white w-60 rounded-md z-20 absolute right-0 invisible tooltip-item"
		>
			<a href="" class="font-semibold pb-4">{{ tooltip }}</a>
			<ul class="list-disc space-y-2">
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

<style>
.tooltip:hover .tooltip-item {
	visibility: visible;
}
</style>
