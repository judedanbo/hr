<script setup>
import { Link } from "@inertiajs/inertia-vue3";
import {
	ArrowDownCircleIcon,
	ArrowPathIcon,
	ArrowUpCircleIcon,
} from "@heroicons/vue/20/solid";

defineProps({
	promotions: Object,
});

const statuses = {
	Paid: "text-green-700 bg-green-50 ring-green-600/20",
	Withdraw: "text-gray-600 bg-gray-50 ring-gray-500/10",
	Overdue: "text-red-700 bg-red-50 ring-red-600/10",
};
</script>
<template>
	<div>
		<div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
			<h2
				class="mx-auto max-w-2xl text-base font-semibold leading-6 text-gray-900 lg:mx-0 lg:max-w-none"
			>
				Promotions in
			</h2>
		</div>
		<div class="mt-6 overflow-hidden border-t border-gray-100">
			<div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
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
							<template v-for="promotion in promotions" :key="promotion.id">
								<tr class="text-sm leading-6 text-gray-900">
									<th
										scope="colgroup"
										colspan="3"
										class="relative isolate py-2 font-semibold"
									>
										<p>{{ promotion[0].rank_name }}</p>
										<div
											class="absolute inset-y-0 right-full -z-10 w-screen border-b border-gray-200 bg-gray-50"
										/>
										<div
											class="absolute inset-y-0 left-0 -z-10 w-screen border-b border-gray-200 bg-gray-50"
										/>
									</th>
								</tr>
								<tr v-for="staff in promotion" :key="staff.id">
									<td class="relative py-5 pr-6">
										<div class="flex gap-x-6">
											<component
												:is="staff.icon"
												class="hidden h-6 w-5 flex-none text-gray-400 sm:block"
												aria-hidden="true"
											/>
											<div class="flex-auto">
												<div class="flex items-start gap-x-3">
													<div
														class="text-sm font-medium leading-6 text-gray-900"
													>
														{{ staff.full_name }}
													</div>
												</div>
												<div
													v-if="staff.staff_number"
													class="mt-1 text-xs leading-5 text-gray-500"
												>
													{{ staff.staff_number }}
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
										<div class="text-sm leading-6 text-gray-900">
											{{ staff.start_date }}
										</div>
										<div class="mt-1 text-xs leading-5 text-gray-500">
											{{ staff.remarks }}
										</div>
									</td>
									<td class="py-5 text-right">
										<div class="flex justify-end">
											<Link
												:href="
													route('staff.show', {
														staff: staff.id,
													})
												"
												class="text-sm font-medium leading-6 text-indigo-600 hover:text-indigo-500"
												>View<span class="hidden sm:inline"> staff</span
												><span class="sr-only"
													>, file #{{ staff.file_number }}</span
												>
											</Link>
										</div>
										<div class="mt-1 text-xs leading-5 text-gray-500">
											File
											<span class="text-gray-900"
												>#{{ staff.file_number }}</span
											>
										</div>
									</td>
								</tr>
							</template>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</template>
