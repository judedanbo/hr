<script setup>
import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import NoItem from "@/Components/NoItem.vue";
import MainTable from "@/Components/MainTable.vue";
import TableHead from "@/Components/TableHead.vue";
import TableBody from "@/Components/TableBody.vue";
import RowHeader from "@/Components/RowHeader.vue";
import TableData from "@/Components/TableData.vue";
import TableRow from "@/Components/TableRow.vue";
import SubMenu from "@/Components/SubMenu.vue";

const emit = defineEmits(["openPosition", "editPosition", "deletePosition"]);
const props = defineProps({
	positions: {
		type: Object,
		required: true,
	},
});

const page = usePage();
const permissions = computed(() => page.props?.auth.permissions);

const subMenuClicked = (action, model) => {
	if (action == "Edit") {
		emit("editPosition", model);
	}
	if (action == "Delete") {
		emit("deletePosition", model);
	}
};

const tableCols = [
	"Name",
	"current occupants",
	"Contact",
	"Roles granted",
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
						<template v-for="position in positions" :key="position.id">
							<TableRow>
								<TableData>
									{{ position.name }}
									<!-- nPositionNameCard :position="position" /> -->
								</TableData>
								<TableData>
									{{ position.current_staff }}
								</TableData>
								<TableData>
									<div
										v-for="contact in position.contacts"
										:key="contact.id"
										class="text-sm"
									>
										{{ contact.contact }}
									</div>
								</TableData>
								<TableData>
									<div v-if="position.roles?.length" class="flex flex-wrap gap-1">
										<span
											v-for="role in position.roles"
											:key="role"
											class="rounded bg-green-50 dark:bg-gray-700 px-1.5 py-0.5 text-xs font-medium text-green-700 dark:text-green-300 ring-1 ring-inset ring-green-600/20"
										>
											{{ role }}
										</span>
									</div>
									<span v-else class="text-xs text-gray-400 dark:text-gray-300">
										—
									</span>
								</TableData>
								<TableData>
									<td class="flex justify-end">
										<SubMenu
											v-if="
												permissions?.includes('update staff') ||
												permissions?.includes('delete staff')
											"
											:can-edit="permissions?.includes('update staff')"
											:can-delete="permissions?.includes('delete staff')"
											:items="['Edit', 'Delete']"
											@itemClicked="
												(action) => subMenuClicked(action, position)
											"
										/>
									</td>
								</TableData>
							</TableRow>
						</template>
					</TableBody>
				</MainTable>
				<slot name="pagination" />
			</div>
			<!-- <NoItem v-else name="Staff" /> -->
		</div>
	</section>
</template>
