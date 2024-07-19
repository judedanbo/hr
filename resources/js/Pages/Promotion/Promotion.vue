<script setup>
import { Link } from "@inertiajs/inertia-vue3";
import Pagination from "@/Components/Pagination.vue";
import { useNavigation } from "@/Composables/navigation";
import { computed } from "vue";
const props = defineProps({
	promotions: {
		type: Object,
		required: true,
	},
	rank: {
		type: String,
		required: true,
	},
	filters: Object,
	// month: String,
	// year: Number,
	// rank: Number
});

const navigation = computed(() => useNavigation(props.promotions));
const getMonth = (month) => {
	let params = new URLSearchParams(document.location.search);
};
</script>
<template>
	<div
		class="mx-auto px-4 sm:px-6 lg:px-8 dark:text-gray-50 flex justify-between"
	>
		<div>
			<h2 class="leading-6 font-bold tracking-wider text-xl">{{ rank }}</h2>
			<h2
				@click="getMonth()"
				class="mx-auto max-w-2xl text-base font-semibold leading-6 text-gray-900 dark: dark:text-gray-50 lg:mx-0 lg:max-w-none capitalize"
			>
				{{ filters.month }} {{ filters.year }} Promotions
			</h2>
		</div>
		<button class="border-1 border-gray-50" onclick="window.print()">
			Print this page
		</button>
	</div>
	<div
		class="mt-6 overflow-hidden border-t border-gray-100 dark:border-gray-600 bg-white dark:bg-gray-700"
	>
		<div class="mx-auto px-4 sm:px-6 lg:px-8">
			<div class="mx-auto max-w-2xl lg:mx-0 lg:max-w-none">
				<table class="w-full text-left">
					<thead class="sr-only">
						<tr>
							<th>Staff</th>
							<th class="hidden sm:table-cell">Promotion Date</th>
							<th>More details</th>
						</tr>
					</thead>
					<tbody>
						<template v-for="ranks in promotions" key="{{ranks.id}}">
							<tr class="text-sm leading-6 text-gray-900">
								<th
									scope="colgroup"
									colspan="3"
									class="relative isolate py-2 font-semibold"
								>
									<p class="dark:text-gray-50">
										{{ "(" + ranks.length + ")" }}
									</p>
									<div
										class="absolute inset-y-0 right-full -z-10 w-screen border-b border-gray-200 dark:border-gray-500 bg-gray-50 dark:bg-gray-800"
									/>
									<div
										class="absolute inset-y-0 left-0 -z-10 w-screen border-b border-gray-200 dark:border-gray-500 bg-gray-50 dark:bg-gray-800"
									/>
								</th>
							</tr>
							<tr v-for="staff in ranks" :key="staff.id">
								<td class="relative py-5 pr-6">
									<div class="flex gap-x-6">
										<div class="flex-auto">
											<div class="flex items-start gap-x-3">
												<div
													class="text-sm font-medium leading-6 text-gray-900 dark:text-gray-50"
												>
													{{ staff.full_name }}
												</div>
											</div>
											<div
												class="mt-1 text-xs leading-5 text-gray-500 dark:text-gray-200"
											>
												{{ staff.staff_number }}
												&middot;
												<span
													class="rounded-full px-3 py-1 bg-gray-200 dark:bg-gray-500"
													>{{ staff.status }}</span
												>
											</div>
										</div>
									</div>
									<div
										class="absolute bottom-0 right-full h-px w-screen bg-gray-100"
									/>
									<div
										class="absolute bottom-0 left-0 h-px w-screen bg-gray-100"
									/>
								</td>
								<td class="hidden py-5 pr-6 sm:table-cell">
									<div
										class="text-sm leading-6 text-gray-900 dark:text-gray-50"
									>
										{{ staff.start_date }}
									</div>
									<div
										class="mt-1 text-xs leading-5 text-gray-500 dark:text-gray-200"
									>
										{{ staff.remarks }}
									</div>
								</td>
								<td class="py-5 text-right">
									<div class="flex justify-end">
										<Link
											class="text-sm font-medium leading-6 text-green-600 hover:text-green-500 dark:text-green-50 hover:dark:text-gray-50"
											:href="route('staff.show', { staff: staff.id })"
											>View<span class="hidden sm:inline"> staff</span
											><span class="sr-only"
												>, staff #{{ staff.file_number }},
												{{ staff.client }}</span
											>
										</Link>
									</div>
									<div
										class="mt-1 text-xs leading-5 text-gray-500 dark:text-gray-50"
									>
										File
										<span class="text-gray-900 dark:text-gray-200"
											>#{{ staff.file_number }}</span
										>
									</div>
								</td>
							</tr>
						</template>
					</tbody>
				</table>
			</div>
			<Pagination :navigation="navigation" />
		</div>
	</div>
</template>
