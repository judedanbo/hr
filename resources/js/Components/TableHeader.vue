<script setup>
import { debouncedWatch } from "@vueuse/core";

import { ref, watch } from "vue";
import { PlusIcon } from "@heroicons/vue/24/outline";
import InfoCard from "@/Components/InfoCard.vue";

const emit = defineEmits(["actionClicked", "searchEntered"]);

const props = defineProps({
	title: {
		type: String,
		required: true,
	},
	total: {
		type: Number,
		default: 0,
	},
	actionText: {
		type: String,
		default: "Add",
	},
	search: {
		type: String,
		default: "",
	},
});
const search = ref(props.search);
debouncedWatch(
	search,
	() => {
		emit("searchEntered", search.value);
	},
	{ debounce: 300 },
);
</script>
<template>
	<section class="sm:flex items-center justify-between my-2">
		<FormKit
			v-model="search"
			prefix-icon="search"
			type="search"
			:placeholder="`Search ${title}...`"
			autofocus
		/>
		<InfoCard :title="title" :value="total" link="#" />

		<a
			class="ml-auto flex items-center gap-x-1 rounded-md bg-green-600 dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600"
			href="#"
			@click.prevent="emit('actionClicked')"
		>
			<PlusIcon class="-ml-1.5 h-5 w-5" aria-hidden="true" />
			{{ actionText }}
		</a>
	</section>
</template>
