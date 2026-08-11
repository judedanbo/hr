<script setup>
import DataTable from "@/Components/UI/DataTable.vue";
import RowHeader from "@/Components/RowHeader.vue";
import TableData from "@/Components/TableData.vue";
import TableRow from "@/Components/TableRow.vue";
import { router } from "@inertiajs/vue3";

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
	<DataTable :has-items="roles.total > 0" name="Roles">
		<template #head>
			<RowHeader v-for="(column, id) in tableCols" :key="id">
				{{ column }}
			</RowHeader>
		</template>
		<template #body>
			<TableRow
				v-for="role in roles.data"
				:key="role.id"
				clickable
				@click="openRole(role.id)"
			>
				<TableData primary>{{ role.name }}</TableData>
				<TableData>{{ role.users_count }}</TableData>
			</TableRow>
		</template>
		<template #footer>
			<slot name="pagination" />
		</template>
	</DataTable>
</template>
