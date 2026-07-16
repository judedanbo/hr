<script setup>
import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import MainTable from "@/Components/MainTable.vue";
import TableHead from "@/Components/TableHead.vue";
import TableBody from "@/Components/TableBody.vue";
import RowHeader from "@/Components/RowHeader.vue";
import TableData from "@/Components/TableData.vue";
import TableRow from "@/Components/TableRow.vue";

const emit = defineEmits(["editWindow", "deleteWindow"]);
defineProps({
	windows: { type: Array, required: true },
});

const page = usePage();
const permissions = computed(() => page.props?.auth.permissions);
const canManage = computed(() =>
	permissions.value?.includes("manage leave planning windows"),
);

const tableCols = [
	"Year",
	"Opens",
	"Closes",
	"Status",
	"Late entries",
	"Full plan",
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
						<template v-for="window in windows" :key="window.id">
							<TableRow>
								<TableData>{{ window.year }}</TableData>
								<TableData>{{ window.opens_at }}</TableData>
								<TableData>{{ window.closes_at }}</TableData>
								<TableData>
									<span
										:class="[
											window.is_open
												? 'bg-green-100 text-green-800'
												: 'bg-gray-100 text-gray-800',
											'px-2 py-1 rounded-full text-xs font-medium',
										]"
									>
										{{ window.is_open ? "Open" : "Closed" }}
									</span>
								</TableData>
								<TableData>{{
									window.allow_after_close ? "Allowed" : "—"
								}}</TableData>
								<TableData>{{
									window.require_full_plan ? "Required" : "—"
								}}</TableData>
								<TableData>
									<div class="flex justify-end gap-x-3 text-sm">
										<button
											v-if="canManage"
											type="button"
											class="text-green-700 hover:underline"
											@click="emit('editWindow', window)"
										>
											Edit
										</button>
										<button
											v-if="canManage"
											type="button"
											class="text-red-700 hover:underline"
											@click="emit('deleteWindow', window)"
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
