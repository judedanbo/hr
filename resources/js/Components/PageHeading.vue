<script setup>
import { ref } from "vue";
import { Link } from "@inertiajs/vue3";
import NewBreadcrumb from "./NewBreadcrumb.vue";
import SearchInput from "./SearchInput.vue";
import { ChevronLeftIcon, ChevronRightIcon } from "@heroicons/vue/20/solid";

const props = defineProps({
	name: {
		type: String,
		required: true,
	},
	backLink: {
		type: String,
		required: false,
		default: () => route("dashboard"),
	},
	links: {
		type: Array,
		required: false,
		default: () => [],
	},
	search: {
		type: String,
		default: () => "",
	},
});
const searchValue = ref(props.search);
const emit = defineEmits(["searchStaff"]);

const emitSearch = (value) => {
	searchValue.value = value;
	emit("searchStaff", value);
};
</script>
<template>
	<div>
		<div class="pt-4">
			<nav class="sm:hidden" aria-label="Back">
				<Link
					:href="backLink"
					class="flex items-center text-sm font-medium text-gray-400 hover:text-gray-200"
				>
					<ChevronLeftIcon
						class="-ml-1 mr-1 h-5 w-5 flex-shrink-0 text-gray-500"
						aria-hidden="true"
					/>
					Back
				</Link>
			</nav>
			<slot name="breadcrumb" />
		</div>
		<div class="mt-2 md:flex md:items-center md:justify-between">
			<div class="min-w-0 flex-1">
				<h2
					class="text-2xl font-bold leading-7 text-gray-700 dark:text-white sm:truncate sm:text-3xl sm:tracking-tight"
				>
					{{ name }}
				</h2>
			</div>
			<SearchInput
				:search="searchValue"
				@search="(searchValue) => emitSearch(searchValue)"
			/>
			<slot name="actions" />
		</div>
		<slot name="stats" />
	</div>
</template>
