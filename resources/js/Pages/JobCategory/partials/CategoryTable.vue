<script setup>
import NoItem from "@/Components/NoItem.vue";
import MainTable from "@/Components/MainTable.vue";
import TableHead from "@/Components/TableHead.vue";
import TableBody from "@/Components/TableBody.vue";
import RowHeader from "@/Components/RowHeader.vue";
import TableData from "@/Components/TableData.vue";
import TableRow from "@/Components/TableRow.vue";
import CategoryName from "./CategoryName.vue";

const emit = defineEmits(["openCategory"]);
defineProps({
	categories: { type: Object, required: true },
});
const tableCols = ["Harmonized Grade", "Level/Category", "Grades", "No. Staff"];
</script>
<template>
	<section class="flex flex-col mt-6 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
		<div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
			<div
				v-if="categories.length > 0"
				class="overflow-hidden border-b border-gray-200 rounded-md shadow-md"
			>
				<MainTable>
					<TableHead>
						<template v-for="(column, id) in tableCols" :key="id">
							<RowHeader>{{ column }}</RowHeader>
						</template>
					</TableHead>
					<TableBody>
						<template v-for="category in categories" :key="category.id">
							<TableRow @click="emit('openCategory', category.id)">
								<TableData>
									<CategoryName :category="category" />
								</TableData>
								<TableData align="center">
									{{ category.level }}
								</TableData>
								<TableData align="center">
									{{ category.jobs }}
								</TableData>
								<TableData align="center"> {{ category.staff }}</TableData>
							</TableRow>
						</template>
					</TableBody>
				</MainTable>
				<slot name="pagination" />
			</div>
			<NoItem v-else name="Job categories" />
		</div>
	</section>
</template>
