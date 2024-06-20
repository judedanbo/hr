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
	users: {
		type: Object,
		required: true,
	},
});

const tableCols = ["User", "Permissions"];
</script>

<template>
	<section class="flex flex-col mt-6 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
		<div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
			<div
				v-if="users.total > 0"
				class="overflow-hidden border-b border-gray-200 rounded-md shadow-md"
			>
				<MainTable>
					<TableHead>
						<template v-for="(column, id) in tableCols" :key="id">
							<RowHeader>{{ column }}</RowHeader>
						</template>
					</TableHead>
					<TableBody>
						<template v-for="user in users.data" :key="user.id">
							<TableRow @click="emit('openRole', user.id)">
								<TableData>
									{{ user.name }}
									<!-- <RoleNameCard :user="user" /> -->
								</TableData>
								<TableData>
									{{ user.permissions_count }}
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
