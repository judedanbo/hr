<script setup>
import NoItem from "@/Components/NoItem.vue";
import MainTable from "@/Components/MainTable.vue";
import TableHead from "@/Components/TableHead.vue";
import TableBody from "@/Components/TableBody.vue";
import RowHeader from "@/Components/RowHeader.vue";
import TableData from "@/Components/TableData.vue";
import TableRow from "@/Components/TableRow.vue";
import RegionName from "./RegionName.vue";

const emit = defineEmits(["openRegion"]);
defineProps({
	regions: { type: Object, required: true },
});
const tableCols = [
	"Region name",
	// "districts",
	"Offices",
	"total Units",
	"Total Staff",
];
</script>
<template>
	<section class="flex flex-col mt-6 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
		<div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
			<div
				v-if="regions.length > 0"
				class="overflow-hidden border-b border-gray-200 rounded-md shadow-md"
			>
				<MainTable>
					<TableHead>
						<template v-for="(column, id) in tableCols" :key="id">
							<RowHeader>{{ column }}</RowHeader>
						</template>
					</TableHead>
					<TableBody>
						<template v-for="region in regions" :key="region.id">
							<TableRow @click="emit('openRegion', region.id)">
								<TableData>
									<RegionName :region="region" />
								</TableData>

								<TableData align="center">
									{{ region.offices_count?.toLocaleString() }}
								</TableData>
								<TableData align="center">
									{{ region.units_count?.toLocaleString() }}
								</TableData>

								<TableData align="center">
									{{ region.staff_count?.toLocaleString() }}
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
