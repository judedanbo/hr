<template>
	<div>
		<div class="relative mt-2 rounded-md shadow-sm">
			<div
				class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3"
			>
				<MagnifyingGlassIcon
					class="h-5 w-5 text-green-500"
					aria-hidden="true"
				/>
			</div>
			<input
				id="search"
				v-model="searchValue"
				type="search"
				name="search"
				class="block w-full rounded-md border-0 py-1.5 pl-10 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-green-600 sm:text-sm sm:leading-6"
				placeholder="search ..."
				autofocus
			/>
		</div>
	</div>
</template>

<script setup>
import { ref } from "vue";
import { debouncedWatch } from "@vueuse/core";

import { MagnifyingGlassIcon } from "@heroicons/vue/20/solid";
const emit = defineEmits(["search"]);

const props = defineProps({
	search: {
		type: String,
		default: "",
	},
});
const searchValue = ref(props.search);

debouncedWatch(
	searchValue,
	() => {
		emit("search", searchValue.value);
	},
	{ debounce: 500 },
);
</script>
