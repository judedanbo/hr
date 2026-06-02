<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head } from "@inertiajs/vue3";
import BreadCrumpVue from "@/Components/BreadCrump.vue";

defineProps({
	year: { type: [Number, String], default: null },
	ledger: { type: Array, default: () => [] },
});

const links = [{ name: "Leave Balance", url: "" }];
</script>

<template>
	<MainLayout>
		<Head title="Leave Balance" />
		<main class="max-w-7xl mx-auto sm:px-6 lg:px-8">
			<BreadCrumpVue :links="links" />
			<div class="mt-6">
				<h1 class="text-xl font-semibold text-gray-800 dark:text-gray-100">
					My Leave Balance
					<span v-if="year" class="text-gray-400">— {{ year }}</span>
				</h1>

				<div
					v-if="!ledger.length"
					class="mt-6 rounded-md bg-gray-50 dark:bg-gray-700 p-8 text-center text-gray-600 dark:text-gray-200"
				>
					No leave entitlement is configured for you this year.
				</div>

				<section v-else class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
					<div
						v-for="row in ledger"
						:key="row.leave_type_id"
						class="rounded-md border border-gray-200 dark:border-gray-600 p-4 bg-white dark:bg-gray-800"
					>
						<p class="font-semibold text-gray-800 dark:text-gray-100">
							<span
								class="inline-block h-2 w-2 rounded-full mr-2 align-middle"
								:style="{ backgroundColor: row.color || '#9ca3af' }"
							/>
							{{ row.leave_type }}
						</p>
						<dl class="mt-3 grid grid-cols-4 gap-2 text-center text-sm">
							<div>
								<dt class="text-gray-400">Assigned</dt>
								<dd class="font-semibold">{{ row.assigned }}</dd>
							</div>
							<div>
								<dt class="text-gray-400">Planned</dt>
								<dd class="font-semibold">{{ row.planned }}</dd>
							</div>
							<div>
								<dt class="text-gray-400">Taken</dt>
								<dd class="font-semibold">{{ row.taken }}</dd>
							</div>
							<div>
								<dt class="text-gray-400">Remaining</dt>
								<dd class="font-semibold text-green-700">
									{{ row.remaining }}
								</dd>
							</div>
						</dl>
						<div
							class="mt-3 h-2 w-full rounded-full bg-gray-200 dark:bg-gray-600"
						>
							<div
								class="h-2 rounded-full bg-green-600"
								:style="{
									width:
										Math.min(
											100,
											row.assigned ? (row.taken / row.assigned) * 100 : 0,
										) + '%',
								}"
							/>
						</div>
						<p class="mt-1 text-xs text-gray-400">
							{{ row.taken }} of {{ row.assigned }} day(s) taken
						</p>
					</div>
				</section>
			</div>
		</main>
	</MainLayout>
</template>
