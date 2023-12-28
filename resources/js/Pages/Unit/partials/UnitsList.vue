<script setup>
import NoItem from "@/Components/NoItem.vue";
import MainTable from "@/Components/MainTable.vue";
import TableHead from "@/Components/TableHead.vue";
import TableBody from "@/Components/TableBody.vue";
import RowHeader from "@/Components/RowHeader.vue";
import TableData from "@/Components/TableData.vue";
import TableRow from "@/Components/TableRow.vue";
import UnitNameCard from "./UnitNameCard.vue";
defineProps({
	units: { type: Array, required: true },
});
const emit = defineEmits(["openUnit"]);
const tableCols = ["Name", "Units", "Staff"];
</script>

<template>
	<section class="flex flex-col mt-6 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
		<div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
			<div
				v-if="units.length > 0"
				class="overflow-hidden border-b border-gray-200 rounded-md shadow-md"
			>
				<MainTable>
					<TableHead>
						<template v-for="(column, id) in tableCols" :key="id">
							<RowHeader>{{ column }}</RowHeader>
						</template>
					</TableHead>
					<TableBody>
						<template v-for="unit in units" :key="unit.id">
							<TableRow @click="emit('openUnit', unit.id)">
								<TableData>
									<UnitNameCard :unit="unit" />
								</TableData>
								<TableData align="right">
									{{ unit.units.toLocaleString() }}
									<!-- <StaffEmploymentCard :staff="unit" /> -->
								</TableData>
								<TableData align="right">
									{{ unit.staff.toLocaleString() }}
									<!-- <StaffRetirementCard :staff="unit" /> -->
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
