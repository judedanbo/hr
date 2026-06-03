<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, router } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import { useToggle } from "@vueuse/core";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import Pagination from "@/Components/Pagination.vue";
import Modal from "@/Components/NewModal.vue";
import MainTable from "@/Components/MainTable.vue";
import TableHead from "@/Components/TableHead.vue";
import TableBody from "@/Components/TableBody.vue";
import RowHeader from "@/Components/RowHeader.vue";
import TableData from "@/Components/TableData.vue";
import TableRow from "@/Components/TableRow.vue";
import NoItem from "@/Components/NoItem.vue";
import { useNavigation } from "@/Composables/navigation";

const props = defineProps({
	requests: { type: Object, required: true },
});

const navigation = computed(() => useNavigation(props.requests));

const openApprove = ref(false);
const toggleApprove = useToggle(openApprove);
const openDecline = ref(false);
const toggleDecline = useToggle(openDecline);
const selected = ref(null);
const approvedDays = ref(0);
const declineReason = ref("");

const approveRow = (row) => {
	selected.value = row;
	approvedDays.value = row.requested_days;
	toggleApprove();
};
const declineRow = (row) => {
	selected.value = row;
	declineReason.value = "";
	toggleDecline();
};

const submitApprove = () => {
	router.post(
		route("leave-approvals.approve", { leaveRequest: selected.value.id }),
		{ approved_days: approvedDays.value },
		{ preserveScroll: true, onSuccess: () => toggleApprove() },
	);
};
const submitDecline = () => {
	router.post(
		route("leave-approvals.decline", { leaveRequest: selected.value.id }),
		{ decline_reason: declineReason.value },
		{ preserveScroll: true, onSuccess: () => toggleDecline() },
	);
};

const links = [{ name: "Leave Approvals", url: "" }];
const tableCols = [
	"Staff",
	"Type",
	"Start",
	"End",
	"Days",
	"Remaining",
	"Action",
];
</script>

<template>
	<MainLayout>
		<Head title="Leave Approvals" />
		<main class="max-w-7xl mx-auto sm:px-6 lg:px-8">
			<BreadCrumpVue :links="links" />
			<div
				class="overflow-hidden shadow-sm sm:rounded-lg px-6 border-b border-gray-200"
			>
				<h1 class="text-xl font-semibold text-gray-800 dark:text-gray-100 my-4">
					Pending Leave Approvals
				</h1>
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
											<TableData>{{ row.staff }}</TableData>
											<TableData>{{ row.leave_type }}</TableData>
											<TableData>{{ row.start_date }}</TableData>
											<TableData>{{ row.end_date }}</TableData>
											<TableData>{{ row.requested_days }}</TableData>
											<TableData>{{ row.remaining }}</TableData>
											<TableData>
												<div class="flex justify-end gap-x-3 text-sm">
													<button
														type="button"
														class="text-green-700 hover:underline"
														@click="approveRow(row)"
													>
														Approve
													</button>
													<button
														type="button"
														class="text-red-700 hover:underline"
														@click="declineRow(row)"
													>
														Decline
													</button>
												</div>
											</TableData>
										</TableRow>
									</template>
								</TableBody>
							</MainTable>
							<NoItem v-else name="pending approvals" />
							<Pagination :navigation="navigation" />
						</div>
					</div>
				</section>
			</div>
		</main>

		<Modal :show="openApprove" @close="toggleApprove()">
			<main class="px-8 py-8 bg-gray-100 dark:bg-gray-700">
				<h2 class="text-xl pb-2 dark:text-gray-100">Approve leave</h2>
				<p class="text-sm text-gray-600 dark:text-gray-200 pb-3">
					{{ selected?.staff }} — {{ selected?.leave_type }} ({{
						selected?.requested_days
					}}
					requested)
				</p>
				<label class="block text-sm text-gray-700 dark:text-gray-200"
					>Days to approve</label
				>
				<input
					v-model.number="approvedDays"
					type="number"
					min="1"
					:max="selected?.requested_days"
					class="mt-1 w-full rounded-md border-gray-300 dark:bg-gray-600 dark:text-gray-100"
				/>
				<div class="mt-4 flex justify-end gap-x-3">
					<button
						type="button"
						class="px-3 py-2 text-sm"
						@click="toggleApprove()"
					>
						Cancel
					</button>
					<button
						type="button"
						class="rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white hover:bg-green-500"
						@click="submitApprove()"
					>
						Approve
					</button>
				</div>
			</main>
		</Modal>

		<Modal :show="openDecline" @close="toggleDecline()">
			<main class="px-8 py-8 bg-gray-100 dark:bg-gray-700">
				<h2 class="text-xl pb-2 dark:text-gray-100">Decline leave</h2>
				<p class="text-sm text-gray-600 dark:text-gray-200 pb-3">
					{{ selected?.staff }} — {{ selected?.leave_type }}
				</p>
				<label class="block text-sm text-gray-700 dark:text-gray-200"
					>Reason</label
				>
				<textarea
					v-model="declineReason"
					rows="3"
					class="mt-1 w-full rounded-md border-gray-300 dark:bg-gray-600 dark:text-gray-100"
				/>
				<div class="mt-4 flex justify-end gap-x-3">
					<button
						type="button"
						class="px-3 py-2 text-sm"
						@click="toggleDecline()"
					>
						Cancel
					</button>
					<button
						type="button"
						class="rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white hover:bg-red-500"
						@click="submitDecline()"
					>
						Decline
					</button>
				</div>
			</main>
		</Modal>
	</MainLayout>
</template>
