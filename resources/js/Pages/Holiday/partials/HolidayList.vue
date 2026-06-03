<script setup>
import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import MainTable from "@/Components/MainTable.vue";
import TableHead from "@/Components/TableHead.vue";
import TableBody from "@/Components/TableBody.vue";
import RowHeader from "@/Components/RowHeader.vue";
import TableData from "@/Components/TableData.vue";
import TableRow from "@/Components/TableRow.vue";

const emit = defineEmits(["editRow", "deleteRow"]);
defineProps({
	holidays: { type: Array, required: true },
});

const page = usePage();
const permissions = computed(() => page.props?.auth.permissions);

const tableCols = ["Date", "Name", "Year", "Recurring", "Action"];
</script>

<template>
	<section class="flex flex-col mt-6 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
		<div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
			<div
				class="overflow-hidden border-b border-gray-200 rounded-md shadow-md"
			>
				<MainTable>
					<TableHead>
						<template v-for="(column, id) in tableCols" :key="id">
							<RowHeader>{{ column }}</RowHeader>
						</template>
					</TableHead>
					<TableBody>
						<template v-for="row in holidays" :key="row.id">
							<TableRow>
								<TableData>{{ row.date }}</TableData>
								<TableData>{{ row.name }}</TableData>
								<TableData>{{ row.year }}</TableData>
								<TableData>{{ row.is_recurring ? "Yes" : "No" }}</TableData>
								<TableData>
									<div class="flex justify-end gap-x-3 text-sm">
										<button
											v-if="permissions?.includes('update holiday')"
											type="button"
											class="text-green-700 hover:underline"
											@click="emit('editRow', row)"
										>
											Edit
										</button>
										<button
											v-if="permissions?.includes('delete holiday')"
											type="button"
											class="text-red-700 hover:underline"
											@click="emit('deleteRow', row)"
										>
											Delete
										</button>
									</div>
								</TableData>
							</TableRow>
						</template>
					</TableBody>
				</MainTable>
				<slot name="pagination" />
			</div>
		</div>
	</section>
</template>
