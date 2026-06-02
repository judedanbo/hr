<script setup>
import MainTable from "@/Components/MainTable.vue";
import TableHead from "@/Components/TableHead.vue";
import TableBody from "@/Components/TableBody.vue";
import RowHeader from "@/Components/RowHeader.vue";
import TableData from "@/Components/TableData.vue";
import TableRow from "@/Components/TableRow.vue";
import NoItem from "@/Components/NoItem.vue";

const emit = defineEmits(["editItem", "deleteItem"]);
defineProps({
	items: { type: Array, required: true },
	canEdit: { type: Boolean, default: false },
});

const tableCols = ["Leave type", "Start", "End", "Days", "Note", "Action"];
</script>

<template>
	<section class="flex flex-col mt-6 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
		<div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
			<div
				class="overflow-hidden border-b border-gray-200 rounded-md shadow-md"
			>
				<MainTable v-if="items.length">
					<TableHead>
						<template v-for="(column, id) in tableCols" :key="id">
							<RowHeader>{{ column }}</RowHeader>
						</template>
					</TableHead>
					<TableBody>
						<template v-for="item in items" :key="item.id">
							<TableRow>
								<TableData>{{ item.leave_type }}</TableData>
								<TableData>{{ item.start_date }}</TableData>
								<TableData>{{ item.end_date }}</TableData>
								<TableData>{{ item.proposed_days }}</TableData>
								<TableData>{{ item.note || "—" }}</TableData>
								<TableData>
									<div class="flex justify-end gap-x-3 text-sm">
										<button
											v-if="canEdit"
											type="button"
											class="text-green-700 hover:underline"
											@click="emit('editItem', item)"
										>
											Edit
										</button>
										<button
											v-if="canEdit"
											type="button"
											class="text-red-700 hover:underline"
											@click="emit('deleteItem', item)"
										>
											Delete
										</button>
									</div>
								</TableData>
							</TableRow>
						</template>
					</TableBody>
				</MainTable>
				<NoItem v-else name="planned leave" />
			</div>
		</div>
	</section>
</template>
