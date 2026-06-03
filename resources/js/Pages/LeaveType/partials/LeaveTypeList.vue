<script setup>
import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import MainTable from "@/Components/MainTable.vue";
import TableHead from "@/Components/TableHead.vue";
import TableBody from "@/Components/TableBody.vue";
import RowHeader from "@/Components/RowHeader.vue";
import TableData from "@/Components/TableData.vue";
import TableRow from "@/Components/TableRow.vue";

const emit = defineEmits(["editType", "deleteType"]);
defineProps({
	leaveTypes: { type: Array, required: true },
});

const page = usePage();
const permissions = computed(() => page.props?.auth.permissions);

const tableCols = [
	"Name",
	"Code",
	"Evidence",
	"Gender",
	"Day counting",
	"Notice",
	"Active",
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
						<template v-for="type in leaveTypes" :key="type.id">
							<TableRow>
								<TableData>
									<span
										class="inline-block h-2 w-2 rounded-full mr-2 align-middle"
										:style="{ backgroundColor: type.color || '#9ca3af' }"
									/>
									{{ type.name }}
								</TableData>
								<TableData>{{ type.code }}</TableData>
								<TableData>{{
									type.requires_evidence ? "Required" : "—"
								}}</TableData>
								<TableData>{{
									type.gender_restriction_label || "Any"
								}}</TableData>
								<TableData>
									{{
										type.counts_weekends && type.counts_holidays
											? "Calendar days"
											: "Working days"
									}}
								</TableData>
								<TableData>{{ type.min_notice_days }} day(s)</TableData>
								<TableData>
									<span
										:class="[
											type.is_active
												? 'bg-green-100 text-green-800'
												: 'bg-gray-100 text-gray-800',
											'px-2 py-1 rounded-full text-xs font-medium',
										]"
									>
										{{ type.is_active ? "Active" : "Inactive" }}
									</span>
								</TableData>
								<TableData>
									<div class="flex justify-end gap-x-3 text-sm">
										<button
											v-if="permissions?.includes('update leave type')"
											type="button"
											class="text-green-700 hover:underline"
											@click="emit('editType', type)"
										>
											Edit
										</button>
										<button
											v-if="permissions?.includes('delete leave type')"
											type="button"
											class="text-red-700 hover:underline"
											@click="emit('deleteType', type)"
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
