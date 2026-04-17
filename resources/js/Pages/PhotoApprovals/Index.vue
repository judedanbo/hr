<script setup>
import { Head } from "@inertiajs/vue3";
import { router } from "@inertiajs/vue3";
import NewAuthenticated from "@/Layouts/NewAuthenticated.vue";

defineProps({
	pending: {
		type: Array,
		default: () => [],
	},
});

function approve(personId) {
	router.post(
		route("photo-approvals.approve", { person: personId }),
		{},
		{ preserveScroll: true, onSuccess: () => router.reload() },
	);
}

function reject(personId) {
	router.post(
		route("photo-approvals.reject", { person: personId }),
		{},
		{ preserveScroll: true, onSuccess: () => router.reload() },
	);
}
</script>

<template>
	<Head title="Photo Approvals" />
	<NewAuthenticated>
		<div class="px-4 py-6 sm:px-6 lg:px-8">
			<div class="mb-6">
				<h1 class="text-2xl font-bold text-gray-900 dark:text-gray-50">
					Photo Approvals
				</h1>
				<p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
					Review and approve or reject pending staff profile photo submissions.
				</p>
			</div>

			<div
				v-if="pending.length === 0"
				class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-6 py-12 text-center shadow-sm"
			>
				<p class="text-sm text-gray-500 dark:text-gray-400">
					No pending photo submissions.
				</p>
			</div>

			<div v-else class="overflow-hidden rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700">
				<table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
					<thead class="bg-gray-50 dark:bg-gray-900">
						<tr>
							<th
								class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider"
							>
								Staff Member
							</th>
							<th
								class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider"
							>
								Current Photo
							</th>
							<th
								class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider"
							>
								Pending Photo
							</th>
							<th
								class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider"
							>
								Submitted
							</th>
							<th
								class="px-6 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider"
							>
								Actions
							</th>
						</tr>
					</thead>
					<tbody
						class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700"
					>
						<tr
							v-for="item in pending"
							:key="item.id"
							class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
						>
							<td class="px-6 py-4 whitespace-nowrap">
								<p
									class="text-sm font-semibold text-gray-900 dark:text-gray-50"
								>
									{{ item.name }}
								</p>
							</td>
							<td class="px-6 py-4 whitespace-nowrap">
								<img
									v-if="item.current_image"
									:src="item.current_image"
									alt="Current photo"
									class="w-20 h-20 rounded-lg object-cover border border-gray-200 dark:border-gray-700"
								/>
								<div
									v-else
									class="w-20 h-20 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center"
								>
									<span
										class="text-xs text-gray-400 dark:text-gray-500"
										>None</span
									>
								</div>
							</td>
							<td class="px-6 py-4 whitespace-nowrap">
								<img
									:src="item.pending_image"
									alt="Pending photo"
									class="w-20 h-20 rounded-lg object-cover border-2 border-amber-400 dark:border-amber-500"
								/>
							</td>
							<td class="px-6 py-4 whitespace-nowrap">
								<span class="text-sm text-gray-500 dark:text-gray-400">{{
									item.pending_image_at
								}}</span>
							</td>
							<td
								class="px-6 py-4 whitespace-nowrap text-right space-x-2"
							>
								<button
									type="button"
									class="inline-flex items-center rounded-lg bg-emerald-600 hover:bg-emerald-700 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition-colors"
									@click="approve(item.id)"
								>
									Approve
								</button>
								<button
									type="button"
									class="inline-flex items-center rounded-lg bg-red-600 hover:bg-red-700 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition-colors"
									@click="reject(item.id)"
								>
									Reject
								</button>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
	</NewAuthenticated>
</template>
