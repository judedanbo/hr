<script setup>
import {
	PencilSquareIcon,
	PlusIcon,
	QueueListIcon,
} from "@heroicons/vue/20/solid";
import { Link } from "@inertiajs/inertia-vue3";
import Ranks from "./Ranks.vue";
defineProps({
	category: Object,
});
const emit = defineEmits(["add-rank"]);

function addRank() {
	emit("add-rank");
}
</script>

<template>
	<div class="border-b border-gray-200 bg-white px-4 py-5 sm:px-6">
		<div
			class="-ml-4 -mt-4 flex flex-wrap items-center justify-between sm:flex-nowrap"
		>
			<div class="ml-4 mt-4">
				<div class="flex items-center">
					<div class="flex-shrink-0">
						<QueueListIcon class="h-12 w-12 rounded-full text-gray-400" />
					</div>
					<div class="ml-4">
						<h3 class="text-base font-semibold leading-6 text-gray-900">
							{{ category.name }}
							{{ category.short_name ? "(" + category.short_name + ")" : "" }}
						</h3>
						<p class="text-sm text-gray-500">
							<a href="#">{{ category.jobs.length }} grades</a>
						</p>
						<p v-if="category.parent" class="text-sm text-gray-500">
							Next Grade
							<Link
								:href="
									route('job-category.show', {
										jobCategory: category.parent.id,
									})
								"
								>{{ category.parent.name }}
								{{
									category.parent.short_name
										? "(" + category.parent.short_name + ")"
										: ""
								}}</Link
							>
						</p>
					</div>
				</div>
			</div>
			<!-- <div class="ml-4 mt-4 flex flex-shrink-0">
				<button
					type="button"
					class="relative inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
				>
					<PlusIcon
						class="-ml-0.5 mr-1.5 h-5 w-5 text-gray-400"
						aria-hidden="true"
					/>
					<span>Add rank</span>
				</button>
			</div> -->
		</div>
		<Ranks @add-rank="addRank()" :jobs="category.jobs" class="mt-4" />
	</div>
</template>
