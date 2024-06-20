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
	permissions: {
		type: Object,
		required: true,
	},
});

const tableCols = ["Permissions", "Users"];
</script>

<template>
	<!-- {{ permissions }} -->
	<section class="flex flex-col mt-6 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
		<div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
			<div
				v-if="permissions?.total > 0"
				class="overflow-hidden border-b border-gray-200 rounded-md shadow-md"
			>
				<MainTable>
					<TableHead>
						<template v-for="(column, id) in tableCols" :key="id">
							<RowHeader>{{ column }}</RowHeader>
						</template>
					</TableHead>
					<TableBody>
						<template
							v-for="permission in permissions.data"
							:key="permission.id"
						>
							<TableRow @click="emit('openRole', permission.id)">
								<TableData>
									{{ permission.name }}
									<!-- <RoleNameCard :permission="permission" /> -->
								</TableData>
								<TableData>
									{{ permission.users_count }}
								</TableData>
							</TableRow>
						</template>
					</TableBody>
				</MainTable>
				<slot name="pagination" />
			</div>
			<NoItem v-else name="Permission" />
		</div>
	</section>
</template>
