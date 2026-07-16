<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, router } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import { useToggle } from "@vueuse/core";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import Pagination from "@/Components/Pagination.vue";
import Modal from "@/Components/NewModal.vue";
import Delete from "@/Components/Delete.vue";
import TableHeader from "@/Components/TableHeader.vue";
import MainTable from "@/Components/MainTable.vue";
import TableHead from "@/Components/TableHead.vue";
import TableBody from "@/Components/TableBody.vue";
import RowHeader from "@/Components/RowHeader.vue";
import TableData from "@/Components/TableData.vue";
import TableRow from "@/Components/TableRow.vue";
import { useNavigation } from "@/Composables/navigation";

const props = defineProps({
	adjustments: { type: Object, required: true },
	staffOptions: { type: Array, default: () => [] },
	leaveTypes: { type: Array, default: () => [] },
	leaveYears: { type: Array, default: () => [] },
});

const navigation = computed(() => useNavigation(props.adjustments));
const openAdd = ref(false);
const toggleAdd = useToggle(openAdd);
const openDelete = ref(false);
const toggleDelete = useToggle(openDelete);
const selected = ref(null);

const deleteRow = (row) => {
	selected.value = row;
	toggleDelete();
};
const deleteConfirmed = () => {
	router.delete(
		route("leave-balance-adjustment.delete", {
			leaveBalanceAdjustment: selected.value.id,
		}),
		{ preserveScroll: true, onSuccess: () => toggleDelete() },
	);
};

const submit = (data, node) => {
	router.post(route("leave-balance-adjustment.store"), data, {
		preserveScroll: true,
		onSuccess: () => {
			node.reset();
			toggleAdd();
		},
		onError: (errors) => node.setErrors([], errors),
	});
};

const links = [{ name: "Balance Adjustments", url: "" }];
const tableCols = ["Staff", "Type", "Year", "Days", "Reason", "By", "Action"];
</script>

<template>
	<MainLayout>
		<Head title="Balance Adjustments" />
		<main class="max-w-7xl mx-auto sm:px-6 lg:px-8">
			<BreadCrumpVue :links="links" />
			<div
				class="overflow-hidden shadow-sm sm:rounded-lg px-6 border-b border-gray-200"
			>
				<TableHeader
					title="Leave Balance Adjustments"
					:total="adjustments.total"
					action-text="Add Adjustment"
					@action-clicked="toggleAdd()"
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
									<template v-for="(c, i) in tableCols" :key="i"
										><RowHeader>{{ c }}</RowHeader></template
									>
								</TableHead>
								<TableBody>
									<template v-for="row in adjustments.data" :key="row.id">
										<TableRow>
											<TableData>{{ row.staff }}</TableData>
											<TableData>{{ row.leave_type }}</TableData>
											<TableData>{{ row.year }}</TableData>
											<TableData>
												<span
													:class="
														row.days < 0 ? 'text-red-700' : 'text-green-700'
													"
												>
													{{ row.days > 0 ? "+" : "" }}{{ row.days }}
												</span>
											</TableData>
											<TableData>{{ row.reason }}</TableData>
											<TableData>{{ row.by || "—" }}</TableData>
											<TableData>
												<div class="flex justify-end text-sm">
													<button
														type="button"
														class="text-red-700 hover:underline"
														@click="deleteRow(row)"
													>
														Delete
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

		<Modal :show="openAdd" @close="toggleAdd()">
			<main class="px-8 py-8 bg-gray-100 dark:bg-gray-700">
				<h1 class="text-2xl pb-4 dark:text-gray-100">Add balance adjustment</h1>
				<FormKit type="form" submit-label="Save" @submit="submit">
					<FormKit
						type="select"
						name="staff_id"
						label="Staff"
						placeholder="Select staff"
						:options="staffOptions"
						validation="required"
					/>
					<FormKit
						type="select"
						name="leave_type_id"
						label="Leave type"
						placeholder="Select type"
						:options="leaveTypes"
						validation="required"
					/>
					<FormKit
						type="select"
						name="leave_year_id"
						label="Leave year"
						placeholder="Select year"
						:options="leaveYears"
						validation="required"
					/>
					<FormKit
						type="number"
						name="days"
						label="Days (+/-)"
						help="Use a negative value to deduct"
						validation="required|number"
					/>
					<FormKit
						type="text"
						name="reason"
						label="Reason"
						validation="required"
					/>
				</FormKit>
			</main>
		</Modal>
		<Delete
			:show="openDelete"
			model-name="adjustment"
			@close="toggleDelete()"
			@delete-confirmed="deleteConfirmed()"
		/>
	</MainLayout>
</template>
