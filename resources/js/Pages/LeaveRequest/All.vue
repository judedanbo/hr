<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, router } from "@inertiajs/vue3";
import { computed } from "vue";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import TableHeader from "@/Components/TableHeader.vue";
import Pagination from "@/Components/Pagination.vue";
import MainTable from "@/Components/MainTable.vue";
import TableHead from "@/Components/TableHead.vue";
import TableBody from "@/Components/TableBody.vue";
import RowHeader from "@/Components/RowHeader.vue";
import TableData from "@/Components/TableData.vue";
import TableRow from "@/Components/TableRow.vue";
import LeaveStatusBadge from "@/Components/LeaveStatusBadge.vue";
import { useNavigation } from "@/Composables/navigation";
import { useSearch } from "@/Composables/search";

const props = defineProps({
	requests: { type: Object, required: true },
	filters: { type: Object, default: () => ({}) },
});

const navigation = computed(() => useNavigation(props.requests));
const search = (value) => useSearch(value, route("leave-requests.index"));
const open = (id) =>
	router.visit(route("leave-requests.show", { leaveRequest: id }));

const links = [{ name: "All Leave Requests", url: "" }];
const tableCols = ["Staff", "Type", "Start", "End", "Days", "Status", "Action"];
</script>

<template>
	<MainLayout>
		<Head title="All Leave Requests" />
		<main class="max-w-7xl mx-auto sm:px-6 lg:px-8">
			<BreadCrumpVue :links="links" />
			<div
				class="overflow-hidden shadow-sm sm:rounded-lg px-6 border-b border-gray-200"
			>
				<TableHeader
					title="All Leave Requests"
					:total="requests.total"
					:search="filters.search"
					action-text=""
					@search-entered="(value) => search(value)"
				/>
				<section
					class="flex flex-col mt-6 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8"
				>
					<div
						class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8"
					>
						<div
							class="overflow-hidden border-b border-gray-200 rounded-md shadow-md"
						>
							<MainTable>
								<TableHead>
									<template v-for="(column, id) in tableCols" :key="id">
										<RowHeader>{{ column }}</RowHeader>
									</template>
								</TableHead>
								<TableBody>
									<template v-for="row in requests.data" :key="row.id">
										<TableRow>
											<TableData>{{ row.staff }}</TableData>
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
							<Pagination :navigation="navigation" />
						</div>
					</div>
				</section>
			</div>
		</main>
	</MainLayout>
</template>
