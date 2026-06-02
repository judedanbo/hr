<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import { computed } from "vue";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import Pagination from "@/Components/Pagination.vue";
import MainTable from "@/Components/MainTable.vue";
import TableHead from "@/Components/TableHead.vue";
import TableBody from "@/Components/TableBody.vue";
import RowHeader from "@/Components/RowHeader.vue";
import TableData from "@/Components/TableData.vue";
import TableRow from "@/Components/TableRow.vue";
import NoItem from "@/Components/NoItem.vue";
import LeaveStatusBadge from "@/Components/LeaveStatusBadge.vue";
import { useNavigation } from "@/Composables/navigation";

const props = defineProps({
	requests: { type: Object, required: true },
	statuses: { type: Array, default: () => [] },
	balance: { type: Array, default: () => [] },
	filters: { type: Object, default: () => ({}) },
});

const page = usePage();
const permissions = computed(() => page.props?.auth.permissions);
const canCreate = computed(() =>
	permissions.value?.includes("create leave request"),
);
const navigation = computed(() => useNavigation(props.requests));

const open = (id) =>
	router.visit(route("leave-request.show", { leaveRequest: id }));

const links = [{ name: "My Leave", url: "" }];
const tableCols = ["Type", "Start", "End", "Days", "Status", "Action"];
</script>

<template>
	<MainLayout>
		<Head title="My Leave" />
		<main class="max-w-7xl mx-auto sm:px-6 lg:px-8">
			<BreadCrumpVue :links="links" />
			<div
				class="overflow-hidden shadow-sm sm:rounded-lg px-6 border-b border-gray-200"
			>
				<div class="flex items-center justify-between my-4">
					<h1 class="text-xl font-semibold text-gray-800 dark:text-gray-100">
						My Leave Requests
					</h1>
					<div class="flex items-center gap-x-3">
						<Link
							:href="route('leave-balance.index')"
							class="text-sm text-green-700 hover:underline"
						>
							View full balance
						</Link>
						<Link
							v-if="canCreate"
							:href="route('leave-request.create')"
							class="rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white hover:bg-green-500"
						>
							Request leave
						</Link>
					</div>
				</div>

				<section
					v-if="balance.length"
					class="mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4"
				>
					<div
						v-for="row in balance"
						:key="row.leave_type_id"
						class="rounded-md border border-gray-200 dark:border-gray-600 p-3 bg-white dark:bg-gray-800"
					>
						<p class="text-sm font-semibold text-gray-800 dark:text-gray-100">
							{{ row.leave_type }}
						</p>
						<p class="text-xs text-gray-500 dark:text-gray-300">
							<span class="text-green-700 font-semibold">{{
								row.remaining
							}}</span>
							of {{ row.assigned }} day(s) left
						</p>
					</div>
				</section>
				<section class="flex flex-col -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
					<div
						class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8"
					>
						<div
							class="overflow-hidden border-b border-gray-200 rounded-md shadow-md"
						>
							<MainTable v-if="requests.data.length">
								<TableHead>
									<template v-for="(column, id) in tableCols" :key="id">
										<RowHeader>{{ column }}</RowHeader>
									</template>
								</TableHead>
								<TableBody>
									<template v-for="row in requests.data" :key="row.id">
										<TableRow>
											<TableData>{{ row.leave_type }}</TableData>
											<TableData>{{ row.start_date }}</TableData>
											<TableData>{{ row.end_date }}</TableData>
											<TableData>{{ row.requested_days }}</TableData>
											<TableData
												><LeaveStatusBadge :status="row.status"
											/></TableData>
											<TableData>
												<div class="flex justify-end text-sm">
													<button
														type="button"
														class="text-green-700 hover:underline"
														@click="open(row.id)"
													>
														View
													</button>
												</div>
											</TableData>
										</TableRow>
									</template>
								</TableBody>
							</MainTable>
							<NoItem v-else name="leave requests" />
							<Pagination :navigation="navigation" />
						</div>
					</div>
				</section>
			</div>
		</main>
	</MainLayout>
</template>
