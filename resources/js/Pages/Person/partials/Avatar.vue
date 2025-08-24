<script setup>
import { usePage } from "@inertiajs/vue3";
import { computed } from "vue";

const emit = defineEmits(["change-avatar"]);

const page = usePage();
const permissions = computed(() => page.props?.auth.permissions);
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
	<figure class="relative">
		<img
			v-if="image"
			:class="size == 'sm' ? ' w-12 h-12' : 'w-36 h-36 md:w-48 md:h-48'"
			class="flex-none rounded-xl md:rounded-full bg-gray-50 object-cover object-center"
			:src="image"
			:alt="initials"
		/>
		<div
			v-else
			:class="size == 'sm' ? ' w-12 h-12' : 'w-36 h-36 md:w-48 md:h-48'"
			class="rounded-xl md:rounded-full bg-gray-400 dark:bg-gray-100 flex justify-center items-center"
		>
			<h1
				:class="size == 'sm' ? ' text-xl' : 'text-5xl md:text-7xl'"
				class="text-white dark:text-gray-700 font-bold tracking-widest select-none"
			>
				{{ initials }}
			</h1>
		</div>
		<a
			v-if="
				size !== 'sm' &&
				(permissions?.includes('upload avatar') ||
					permissions?.includes('update avatar'))
			"
			href="#"
			class="absolute w-full h-full top-0 left-0 bg-white opacity-0 z-10 transition-opacity duration-300 hover:opacity-80 rounded-full flex justify-center items-center text-xl text-gray-900 text-bold"
			@click.prevent="emit('change-avatar')"
			>Change Image</a
		>
	</figure>
</template>
