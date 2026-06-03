<script setup>
import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import MainTable from "@/Components/MainTable.vue";
import TableHead from "@/Components/TableHead.vue";
import TableBody from "@/Components/TableBody.vue";
import RowHeader from "@/Components/RowHeader.vue";
import TableData from "@/Components/TableData.vue";
import TableRow from "@/Components/TableRow.vue";

const emit = defineEmits(["editYear", "deleteYear", "cloneYear"]);
defineProps({
	leaveYears: { type: Array, required: true },
});

const page = usePage();
const permissions = computed(() => page.props?.auth.permissions);

const tableCols = [
	"Year",
	"Start",
	"End",
	"Active",
	"Entitlements",
	"Holidays",
	"Action",
];
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
						<template v-for="year in leaveYears" :key="year.id">
							<TableRow>
								<TableData>{{ year.year }}</TableData>
								<TableData>{{ year.start_date }}</TableData>
								<TableData>{{ year.end_date }}</TableData>
								<TableData>
									<span
										:class="[
											year.is_active
												? 'bg-green-100 text-green-800'
												: 'bg-gray-100 text-gray-800',
											'px-2 py-1 rounded-full text-xs font-medium',
										]"
									>
										{{ year.is_active ? "Active" : "Inactive" }}
									</span>
								</TableData>
								<TableData>{{ year.entitlements_count }}</TableData>
								<TableData>{{ year.holidays_count }}</TableData>
								<TableData>
									<div class="flex justify-end gap-x-3 text-sm">
										<button
											v-if="permissions?.includes('update leave year')"
											type="button"
											class="text-green-700 hover:underline"
											@click="emit('editYear', year)"
										>
											Edit
										</button>
										<button
											v-if="permissions?.includes('clone leave year')"
											type="button"
											class="text-blue-700 hover:underline"
											@click="emit('cloneYear', year)"
										>
											Clone
										</button>
										<button
											v-if="permissions?.includes('delete leave year')"
											type="button"
											class="text-red-700 hover:underline"
											@click="emit('deleteYear', year)"
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
