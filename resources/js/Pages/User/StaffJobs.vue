<script setup>
import { Link } from "@inertiajs/vue3";
import { format, differenceInYears } from "date-fns";
defineProps({
	jobs: Object,
});

const formattedDate = (dateString) => {
	const date = new Date(dateString);
	return format(date, "dd MMMM, yyyy");
};

let getAge = (dateString) => {
	const date = new Date(dateString);
	return differenceInYears(new Date(), date);
};
</script>
<template>
	<div class="overflow-hidden bg-white shadow sm:rounded-lg w-full mx-auto">
		<div class="px-4 py-5 sm:px-6">
			<h3 class="text-lg font-medium leading-6 text-gray-900">Job History</h3>
		</div>

		<div class="overflow-x-auto relative shadow-md sm:rounded-lg">
			<div
				class="flex justify-end items-center px-4 bg-white dark:bg-gray-800"
			></div>
			<table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
				<thead
					class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400"
				>
					<tr>
						<th scope="col" class="py-3 px-6">Position</th>
						<th scope="col" class="py-3 px-6 text-right">Start</th>
						<th scope="col" class="py-3 px-6 text-right">End</th>
					</tr>
				</thead>
				<tbody>
					<tr
						v-for="job in jobs"
						:key="job.id"
						class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600"
					>
						<th
							scope="row"
							class="flex items-center py-4 px-6 text-gray-900 whitespace-nowrap dark:text-white"
						>
							{{ job.name }}
						</th>

						<td class="py-4 px-6 text-right space-x-3">
							{{ formattedDate(job.start_date) }}
						</td>
						<td class="py-4 px-6 text-right space-x-3">
							{{ job.end_date ? formattedDate(job.end_date) : "to date" }}
						</td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
</template>
