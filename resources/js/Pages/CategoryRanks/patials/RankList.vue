<script setup>
import NoItem from "@/Components/NoItem.vue";
import MainTable from "@/Components/MainTable.vue";
import TableHead from "@/Components/TableHead.vue";
import TableBody from "@/Components/TableBody.vue";
import RowHeader from "@/Components/RowHeader.vue";
import TableData from "@/Components/TableData.vue";
import TableRow from "@/Components/TableRow.vue";
import { Inertia } from "@inertiajs/inertia";
defineProps({
	ranks: {
		type: Array,
		default: () => {},
	},
});
const tableCols = ["Ranks", "Staff"];
const openRank = (id) => {
	Inertia.visit(route("job.show", id));
};
</script>
<template>
	<MainTable>
		<TableHead>
			<template v-for="(column, id) in tableCols" :key="id">
				<RowHeader>{{ column }}</RowHeader>
			</template>
		</TableHead>
		<TableBody>
			<template v-for="rank in ranks" :key="rank.id">
				<TableRow @row-clicked="openRank(rank.id)">
					<TableData>
						{{ rank.name }}
					</TableData>
					<TableData>
						{{ rank.staff_count }}
					</TableData>
				</TableRow>
			</template>
		</TableBody>
	</MainTable>
</template>
