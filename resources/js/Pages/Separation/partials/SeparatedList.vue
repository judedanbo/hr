<script setup>
import NoItem from "@/Components/NoItem.vue";
import MainTable from "@/Components/MainTable.vue";
import TableHead from "@/Components/TableHead.vue";
import TableBody from "@/Components/TableBody.vue";
import RowHeader from "@/Components/RowHeader.vue";
import TableData from "@/Components/TableData.vue";
import TableRow from "@/Components/TableRow.vue";
import StaffNameCard from "../../Staff/partials/StaffNameCard.vue";
import StaffRetirementCard from "../../Staff/partials/StaffRetirementCard.vue";
// import UnitNameCard from "./UnitNameCard.vue";
defineProps({
	separated: { type: Array, required: true },
});
const emit = defineEmits(["openSeparation"]);
const tableCols = ["Name", "Separation"];
</script>

<template>
	<section class="flex flex-col mt-6 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
		<div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
			<div
				v-if="separated.length > 0"
				class="overflow-hidden border-b border-gray-200 rounded-md shadow-md"
			>
				<MainTable>
					<TableHead>
						<template v-for="(column, id) in tableCols" :key="id">
							<RowHeader>{{ column }}</RowHeader>
						</template>
					</TableHead>
					<TableBody>
						<template v-for="staff in separated" :key="staff.id">
							<!-- {{ separated }} -->
							<TableRow @click="emit('openSeparation', staff.id)">
								<!-- {{ unit }} -->
								<TableData>
									<StaffNameCard :staff="staff" />
								</TableData>
								<!-- <TableData align="right">
									<StaffRetirementCard :staff="staff" />
								</TableData> -->
								<TableData align="right">
									<!-- {{ staff.statuses }} -->
									<div v-for="status in staff.statuses">
										{{ status.status }}
										<p>
											{{ status.start_date }} |
											{{ status.end_date }}
										</p>
										<p class="text-sm">
											{{ staff.note?.note }}
										</p>
									</div>
								</TableData>
							</TableRow>
						</template>
					</TableBody>
				</MainTable>
				<slot name="pagination" />
			</div>
			<NoItem v-else name="Units" />
		</div>
	</section>
</template>
