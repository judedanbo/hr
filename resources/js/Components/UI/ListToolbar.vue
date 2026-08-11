<script setup>
import { ref } from "vue";
import { debouncedWatch } from "@vueuse/core";
import { PlusIcon } from "@heroicons/vue/20/solid";

const emit = defineEmits(["actionClicked", "searchEntered"]);

const props = defineProps({
	title: { type: String, required: true },
	actionText: { type: String, default: "Add" },
	search: { type: String, default: "" },
	showAction: { type: Boolean, default: true },
	showSearch: { type: Boolean, default: true },
});

const searchTerm = ref(props.search);

debouncedWatch(
	searchTerm,
	() => {
		emit("searchEntered", searchTerm.value);
	},
	{ debounce: 300 },
);
</script>
<template>
	<div
		class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
	>
		<div class="w-full sm:max-w-xs">
			<FormKit
				v-if="showSearch"
				v-model="searchTerm"
				prefix-icon="search"
				type="search"
				:placeholder="`Search ${title.toLowerCase()}...`"
				outer-class="$reset w-full"
			/>
		</div>
		<div class="flex shrink-0 items-center gap-2">
			<slot name="actions" />
			<button
				v-if="showAction"
				type="button"
				class="btn btn-primary"
				@click="emit('actionClicked')"
			>
				<PlusIcon class="-ml-0.5 h-5 w-5" aria-hidden="true" />
				{{ actionText }}
			</button>
		</div>
	</div>
</template>
