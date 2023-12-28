<script setup>
import NoItem from "@/Components/NoItem.vue";
import MainTable from "@/Components/MainTable.vue";
import TableHead from "@/Components/TableHead.vue";
import TableBody from "@/Components/TableBody.vue";
import RowHeader from "@/Components/RowHeader.vue";
import TableData from "@/Components/TableData.vue";
import TableRow from "@/Components/TableRow.vue";
import JobName from "./JobName.vue";
import JobCategoryName from "./JobCategoryName.vue";
defineProps({
	jobs: { type: Array, required: true },
});
const emit = defineEmits(["openJob"]);
const tableCols = ["Harmonized Grade", "Grade Category", "Level", "No. Staff"];
</script>

<template>
	<section class="flex flex-col mt-6 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
		<div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
			<div
				v-if="jobs.length > 0"
				class="overflow-hidden border-b border-gray-200 rounded-md shadow-md"
			>
				<MainTable>
					<TableHead>
						<template v-for="(column, id) in tableCols" :key="id">
							<RowHeader>{{ column }}</RowHeader>
						</template>
					</TableHead>
					<TableBody>
						<template v-for="job in jobs" :key="job.id">
							<TableRow @click="emit('openJob', job.id)">
								<TableData>
									<JobName :job="job" />
								</TableData>
								<TableData>
									<JobCategoryName :job="job" />
								</TableData>
								<TableData align="center">
									{{ job.category.level }}
								</TableData>
								<TableData align="right">
									{{ job.staff.toLocaleString() }}
								</TableData>
							</TableRow>
						</template>
					</TableBody>
				</MainTable>
				<slot name="pagination" />
			</div>
			<NoItem v-else name="Jobs" />
		</div>
	</section>
</template>
