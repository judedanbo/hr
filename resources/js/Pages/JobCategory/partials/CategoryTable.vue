<script setup>
import Pagination from "@/Components/Pagination.vue";
import NoItem from "@/Components/NoItem.vue";
import { Link } from "@inertiajs/inertia-vue3";

defineProps({
	categories: Object,
});
</script>
<template>
	<div v-if="categories.total > 0" class="flex flex-col mt-2">
		<div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
			<div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
				<div
					class="overflow-hidden border-b border-gray-200 rounded-md shadow-md"
				>
					<table class="min-w-full overflow-x-scroll divide-y divide-gray-200">
						<thead class="bg-gray-50">
							<tr class="dark:bg-gray-700">
								<th
									scope="col"
									class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase dark:text-gray-100"
								>
									Harmonized Grade
								</th>

								<th
									scope="col"
									class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 dark:text-gray-100 uppercase"
								>
									Level/Category
								</th>
								<th
									scope="col"
									class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 dark:text-gray-100 uppercase"
								>
									Grade
								</th>

								<th
									scope="col"
									class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 dark:text-gray-100 uppercase"
								>
									Staff
								</th>

								<th role="col" class="relative px-6 py-3">
									<span class="sr-only">Edit</span>
								</th>
							</tr>
						</thead>
						<tbody class="bg-white divide-y divide-gray-200">
							<tr
								v-for="category in categories.data"
								:key="category.id"
								class="transition-all hover:bg-gray-100 hover:shadow-lg dark:bg-gray-500 dark:hover:bg-gray-700"
							>
								<td class="px-6 py-4 whitespace-nowrap">
									<div class="flex items-center">
										<div
											class="flex-shrink-0 w-14 h-14 text-white dark:text-gray-500 bg-gray-400 dark:bg-gray-50 font-bold tracking-wider rounded-full flex justify-center items-center"
										>{{ category.short_name }}</div>

										<div class="ml-4">
											<div
												class="text-sm font-medium text-gray-900 dark:text-gray-50"
											>
												{{ category.name }}
												{{
													category.short_name
														? "(" + category.short_name + ")"
														: ""
												}}
											</div>
											<div class="text-sm text-gray-500"></div>
										</div>
									</div>
								</td>

								<td class="px-6 py-4 whitespace-nowrap">
									<div class="text-sm text-gray-900 dark:text-gray-50">
										{{ category.level }}
									</div>
								</td>
								<td class="px-6 py-4 whitespace-nowrap">
									<div
										class="text-sm text-gray-900 dark:text-gray-50 text-center"
									>
										{{ category.jobs }}
									</div>
								</td>
								<td class="px-6 py-4 whitespace-nowrap">
									<div
										class="text-sm text-gray-900 dark:text-gray-50 text-center"
									>
										{{ category.staff }}
									</div>
								</td>

								<td
									class="px-6 py-4 text-sm font-medium text-right whitespace-nowrap"
								>
									<Link
										:href="
											route('job-category.show', {
												jobCategory: category.id,
											})
										"
										class="text-green-600 hover:text-green-900 dark:text-gray-50 dark:hover:text-green-400"
										>Show</Link
									>
								</td>
							</tr>
						</tbody>
					</table>
					<Pagination :records="categories" />
				</div>
			</div>
		</div>
	</div>
	<NoItem v-else name="Job categories" />
</template>
