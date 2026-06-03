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
	entitlements: { type: Array, required: true },
});

const page = usePage();
const permissions = computed(() => page.props?.auth.permissions);

const tableCols = [
	"Year",
	"Leave type",
	"Category",
	"Days",
	"Min service (months)",
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
						<template v-for="row in entitlements" :key="row.id">
							<TableRow>
								<TableData>{{ row.year }}</TableData>
								<TableData>{{ row.leave_type }}</TableData>
								<TableData>{{ row.job_category }}</TableData>
								<TableData>{{ row.days_allowed }}</TableData>
								<TableData>{{ row.min_service_months }}</TableData>
								<TableData>
									<div class="flex justify-end gap-x-3 text-sm">
										<button
											v-if="permissions?.includes('update leave entitlement')"
											type="button"
											class="text-green-700 hover:underline"
											@click="emit('editRow', row)"
										>
											Edit
										</button>
										<button
											v-if="permissions?.includes('delete leave entitlement')"
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
