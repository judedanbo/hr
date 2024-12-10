<script setup>
import { defineProps, computed } from "vue";
import MainTable from "@/Components/MainTable.vue";
import TableHead from "@/Components/TableHead.vue";
import TableBody from "@/Components/TableBody.vue";
import RowHeader from "@/Components/RowHeader.vue";
import TableData from "@/Components/TableData.vue";
import TableRow from "@/Components/TableRow.vue";

const props = defineProps({
	units: {
		type: Array,
		required: true,
	},
	search: {
		type: String,
		default: "",
	},
});
const tableCols = ["units", "Male", "Female", "No. Staff"];
</script>
<template>
	<div class="overflow-y-scroll max-h-80">
		<!-- <a href="">print</a> -->
		<MainTable>
			<TableHead>
				<template v-for="(column, id) in tableCols" :key="id">
					<RowHeader>{{ column }}</RowHeader>
				</template>
			</TableHead>
			<TableBody>
				<template v-for="unit in units" :key="unit.id">
					<TableRow>
						<TableData>
							{{ unit.name }}
						</TableData>
						<TableData align="right">
							<!-- {{ unit.male_staff_count.toLocaleString() }} -->
							<!-- {{ unit }} -->
						</TableData>
						<TableData align="right">
							<!-- {{ unit.male_staff_count.toLocaleString() }} -->
						</TableData>
						<TableData align="right">
							{{ unit.total_staff.toLocaleString() }}
						</TableData>
					</TableRow>
				</template>
			</TableBody>
		</MainTable>
	</div>
</template>
