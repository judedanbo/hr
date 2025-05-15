<script setup>
import NoItem from "@/Components/NoItem.vue";
import MainTable from "@/Components/MainTable.vue";
import TableHead from "@/Components/TableHead.vue";
import TableBody from "@/Components/TableBody.vue";
import RowHeader from "@/Components/RowHeader.vue";
import TableData from "@/Components/TableData.vue";
import TableRow from "@/Components/TableRow.vue";

const emit = defineEmits(["openRole"]);
const props = defineProps({
	roles: {
		type: Array,
		required: true,
	},
});

const tableCols = ["Roles", "Permissions", "Users"];
</script>

<template>
	<section class="flex flex-col mt-6 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
		<div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
			<div
				v-if="roles.length > 0"
				class="overflow-hidden border-b border-gray-200 rounded-md shadow-md"
			>
				<MainTable>
					<TableHead>
						<template v-for="(column, id) in tableCols" :key="id">
							<RowHeader>{{ column }}</RowHeader>
						</template>
					</TableHead>
					<TableBody>
						<template v-for="role in roles" :key="role.id">
							<TableRow @click="emit('openRole', role.id)">
								<TableData>
									{{ role.display_name }}
									<!-- <RoleNameCard :role="role" /> -->
								</TableData>
								<TableData>
									{{ role.permissions_count }}
								</TableData>
								<TableData>
									{{ role.users_count }}
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
