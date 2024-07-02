<script setup>
import NoItem from "@/Components/NoItem.vue";
import MainTable from "@/Components/MainTable.vue";
import TableHead from "@/Components/TableHead.vue";
import TableBody from "@/Components/TableBody.vue";
import RowHeader from "@/Components/RowHeader.vue";
import TableData from "@/Components/TableData.vue";
import TableRow from "@/Components/TableRow.vue";

const emit = defineEmits(["openPromotion"]);
const props = defineProps({
	promotions: {
		type: Object,
		required: true,
	},
});

const tableCols = [
	"Rank",
	"Total Staff",
	"April",
	"October",
	"Due for Promotion",
];
</script>

<template>
	<section class="flex flex-col mt-6 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
		<div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
			<div
				v-if="promotions.data.length > 0"
				class="overflow-hidden border-b border-gray-200 rounded-md shadow-md"
			>
				<MainTable>
					<TableHead>
						<template v-for="(column, id) in tableCols" :key="id">
							<RowHeader :align="column !== 'Rank' ? 'right' : 'left'">{{
								column
							}}</RowHeader>
						</template>
					</TableHead>
					<TableBody>
						<template
							v-for="currentRank in promotions.data"
							:key="currentRank.job_id"
						>
							<TableRow>
								<TableData @click="emit('openPromotion', currentRank.job_id)">
									{{ currentRank.job_name }}
								</TableData>
								<TableData
									align="right"
									@click="emit('openPromotion', currentRank.job_id)"
								>
									{{ currentRank.all_staff }}
								</TableData>
								<TableData
									align="right"
									@click="emit('openPromotion', currentRank.job_id, 'april')"
								>
									{{ currentRank.april }}
								</TableData>
								<TableData
									align="right"
									@click="emit('openPromotion', currentRank.job_id, 'october')"
								>
									{{ currentRank.october }}
								</TableData>
								<TableData
									align="right"
									@click="emit('openPromotion', currentRank.job_id)"
								>
									{{ currentRank.staff_to_promote }}
								</TableData>
							</TableRow>
						</template>
					</TableBody>
				</MainTable>
				<slot name="pagination" />
			</div>
			<NoItem v-else name="Staff" />
		</div>
	</section>
</template>
