<script setup>
import { TrashIcon } from "@heroicons/vue/24/outline";
import { Link, usePage } from "@inertiajs/vue3";
import { computed } from "vue";
const page = usePage();
const permissions = computed(() => page.props.value?.auth.permissions);
defineProps({
	jobs: { type: Array, default: () => {} },
});
const emit = defineEmits([
	"addRank",
	"editRank",
	"deleteRank",
	"restoreRank",
	"destroyRank",
]);
</script>
<template>
	<div class="px-4 sm:px-6 lg:px-8">
		<div class="sm:flex sm:items-center">
			<div class="sm:flex-auto">
				<h1
					class="text-base font-semibold leading-6 text-gray-900 dark:text-gray-50"
				>
					Ranks
				</h1>
				<p class="mt-2 text-sm text-gray-700 dark:text-gray-300">
					A list of all the ranks/grades associated with this harmonized grades.
				</p>
			</div>
			<div class="flex ju gap-4 mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
				<button
					v-if="permissions?.includes('create job')"
					type="button"
					class="block rounded-md bg-green-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600"
					@click="emit('addRank')"
				>
					Add rank to this category
				</button>
				<button
					v-if="permissions?.includes('edit job')"
					type="button"
					class="block rounded-md bg-green-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600"
					@click="emit('editRank')"
				>
					Edit category
				</button>

				<button
					v-if="permissions?.includes('delete job')"
					type="button"
					class="block rounded-md bg-rose-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-rose-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-rose-900"
					@click="emit('deleteRank')"
				>
					Delete category
				</button>
				<!-- <button
					v-if="permissions?.includes('restore job')"
					type="button"
					class="block rounded-md bg-blue-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-rose-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-rose-900"
					@click="emit('restoreRank')"
				>
					Restore rank
				</button> -->
				<!-- <button
					v-if="permissions?.includes('destroy job')"
					type="button"
					class="block rounded-md bg-red-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-rose-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-rose-900"
					@click="emit('destroyRank')"
				>
					Delete rank forever
				</button> -->
			</div>
		</div>
		<div class="mt-8 flow-root">
			<div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
				<div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
					<table class="min-w-full divide-y divide-gray-300">
						<thead>
							<tr>
								<th
									scope="col"
									class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 dark:text-gray-50 sm:pl-0"
								>
									Name
								</th>
								<th
									scope="col"
									class="px-3 py-3.5 text-sm font-semibold text-gray-900 text-right dark:text-gray-50"
								>
									Staff
								</th>
								<th
									scope="col"
									class="px-3 py-3.5 text-sm font-semibold text-gray-900 text-right dark:text-gray-50"
								>
									Due for Promotion
								</th>
								<th
									scope="col"
									class="px-3 py-3.5 text-sm font-semibold text-gray-900 dark:text-gray-50 text-right"
								>
									All time
								</th>

								<th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-0">
									<span class="sr-only">actions</span>
								</th>
							</tr>
						</thead>
						<tbody
							class="divide-y divide-gray-200 dark:divide-gray-800 bg-white dark:bg-gray-700"
						>
							<tr v-for="job in jobs" :key="job.id">
								<td class="whitespace-nowrap py-5 pl-4 pr-3 text-sm sm:pl-0">
									<div class="flex items-center">
										<!-- <div class="h-11 w-11 flex-shrink-0">
                        <img class="h-11 w-11 rounded-full" :src="job.image" alt="" />
                      </div> -->
										<div class="ml-4">
											<div class="font-medium text-gray-900 dark:text-gray-50">
												{{ job.name }}
											</div>
										</div>
									</div>
								</td>
								<td
									class="whitespace-nowrap px-3 py-5 text-sm text-gray-500 dark:text-gray-200"
								>
									<div class="text-gray-900 dark:text-gray-50 text-right">
										{{ job.staff_count.toLocaleString() }}
									</div>
								</td>
								<td
									class="whitespace-nowrap px-3 py-5 text-sm text-gray-500 dark:text-gray-200"
								>
									<div class="text-gray-900 dark:text-gray-50 text-right">
										{{ job.promotion_count.toLocaleString() }}
									</div>
								</td>
								<td
									class="whitespace-nowrap px-3 py-5 text-sm text-gray-500 dark:text-gray-200"
								>
									<div class="text-gray-900 dark:text-gray-50 text-right">
										{{ job.all_count.toLocaleString() }}
									</div>
								</td>

								<td
									class="relative whitespace-nowrap py-5 pl-3 pr-4 text-right text-sm font-medium sm:pr-0"
								>
									<Link
										:href="route('job.show', { job: job.id })"
										class="text-green-600 hover:text-green-900"
										>View<span class="sr-only">, {{ job.name }}</span></Link
									>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</template>
