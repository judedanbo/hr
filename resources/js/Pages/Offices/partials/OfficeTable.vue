<script setup>
import NoItem from "@/Components/NoItem.vue";
import MainTable from "@/Components/MainTable.vue";
import TableHead from "@/Components/TableHead.vue";
import TableBody from "@/Components/TableBody.vue";
import RowHeader from "@/Components/RowHeader.vue";
import TableData from "@/Components/TableData.vue";
import TableRow from "@/Components/TableRow.vue";
import OfficeName from "./OfficeName.vue";

const emit = defineEmits(["openOffice"]);
defineProps({
	offices: { type: Object, required: true },
});
const tableCols = [
	"office name",
	// "districts",
	"total Units",
	"Total Staff",
];
</script>
<template>
	<section class="flex flex-col mt-6 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
		<div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
			<div
				v-if="offices.length > 0"
				class="overflow-hidden border-b border-gray-200 rounded-md shadow-md"
			>
				<MainTable>
					<TableHead>
						<template v-for="(column, id) in tableCols" :key="id">
							<RowHeader>{{ column }}</RowHeader>
						</template>
					</TableHead>
					<TableBody>
						<template v-for="office in offices" :key="office.id">
							<TableRow @click="emit('openOffice', office.id)">
								<TableData>
									<OfficeName :office="office" />
								</TableData>

								<!-- <TableData align="center"> -->
								<!-- {{ office.units_count?.toLocaleString() }} -->
								<!-- </TableData> -->
								<TableData align="center">
									{{ office.staff_count?.toLocaleString() }}
								</TableData>

								<TableData align="center">
									{{ office.staff_count?.toLocaleString() }}
								</TableData>
							</TableRow>
						</template>
					</TableBody>
				</MainTable>
				<slot name="pagination" />
			</div>
			<NoItem v-else name="Harmonized Grades" />
		</div>
	</section>
</template>
