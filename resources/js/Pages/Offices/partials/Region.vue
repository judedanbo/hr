<script setup>
import {
	PencilSquareIcon,
	PlusIcon,
	QueueListIcon,
} from "@heroicons/vue/20/solid";
import { Link } from "@inertiajs/vue3";
import Offices from "./Units.vue";
defineProps({
	region: Object,
});
const emit = defineEmits(["addRank", "editRank", "deleteRank"]);
</script>

<template>
	<div
		class="border-b border-gray-200 bg-white dark:bg-gray-700 px-4 py-5 sm:px-6 rounded-lg"
	>
		<div
			class="-ml-4 -mt-4 flex flex-wrap items-center justify-between sm:flex-nowrap"
		>
			<div class="ml-4 mt-4">
				<div class="flex items-center">
					<div class="flex-shrink-0">
						<QueueListIcon class="h-12 w-12 rounded-full text-gray-400" />
					</div>
					<div class="ml-4">
						<h3
							class="text-base font-semibold leading-6 text-gray-900 dark:text-gray-50"
						>
							{{ region.name }}
							{{ region.short_name ? "(" + region.short_name + ")" : "" }}
						</h3>
						<p class="text-sm text-gray-500 dark:text-gray-400">
							<!-- <a href="#">{{ region.jobs.length }} rank(s)</a> -->
						</p>
						<p
							v-if="region.parent"
							class="text-sm text-gray-500 dark:text-gray-400"
						>
							Next Grade
							<Link
								:href="
									route('region.show', {
										region: region.parent.id,
									})
								"
								>{{ region.parent.name }}
								{{
									region.parent.short_name
										? "(" + region.parent.short_name + ")"
										: ""
								}}</Link
							>
						</p>
					</div>
				</div>
			</div>
		</div>
		<Offices
			class="mt-4"
			@add-rank="emit('addRank')"
			@edit-rank="emit('editRank')"
			@delete-rank="emit('deleteRank')"
			@restore-rank="emit('restoreRank')"
			@destroy-rank="emit('destroyRank')"
		/>
	</div>
</template>
