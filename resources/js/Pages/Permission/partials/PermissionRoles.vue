<script setup>
import NoItem from "@/Components/NoItem.vue";
import MainTable from "@/Components/MainTable.vue";
import TableHead from "@/Components/TableHead.vue";
import TableBody from "@/Components/TableBody.vue";
import RowHeader from "@/Components/RowHeader.vue";
import TableData from "@/Components/TableData.vue";
import TableRow from "@/Components/TableRow.vue";
import { router } from "@inertiajs/vue3";

const emit = defineEmits(["openRole"]);
const props = defineProps({
	roles: {
		type: Object,
		required: true,
	},
});

const tableCols = ["Role", "Users"];

const openRole = (roleId) => {
	router.visit(route("role.show", { role: roleId }));
};
</script>

<template>
	<section class="flex flex-col mt-6 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
		<div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
			<div
				v-if="roles.total > 0"
				class="overflow-hidden border-b border-gray-200 rounded-md shadow-md"
			>
				<MainTable>
					<TableHead>
						<template v-for="(column, id) in tableCols" :key="id">
							<RowHeader>{{ column }}</RowHeader>
						</template>
					</TableHead>
					<TableBody>
						<template v-for="role in roles.data" :key="role.id">
							<TableRow @click="openRole(role.id)">
								<TableData>
									{{ role.name }}
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
			<NoItem v-else name="Roles" />
		</div>
	</section>
</template>
