<script setup>
import DataTable from "@/Components/UI/DataTable.vue";
import RowHeader from "@/Components/RowHeader.vue";
import TableData from "@/Components/TableData.vue";
import TableRow from "@/Components/TableRow.vue";
import { router } from "@inertiajs/vue3";

const props = defineProps({
	users: {
		type: Object,
		required: true,
	},
});

const tableCols = ["User", "Roles"];

const openUser = (userId) => {
	router.visit(route("user.show", { user: userId }));
};
</script>

<template>
	<DataTable :has-items="users.total > 0" name="Users">
		<template #head>
			<RowHeader v-for="(column, id) in tableCols" :key="id">
				{{ column }}
			</RowHeader>
		</template>
		<template #body>
			<TableRow
				v-for="user in users.data"
				:key="user.id"
				clickable
				@click="openUser(user.id)"
			>
				<TableData primary>{{ user.name }}</TableData>
				<TableData>{{ user.roles_count }}</TableData>
			</TableRow>
		</template>
		<template #footer>
			<slot name="pagination" />
		</template>
	</DataTable>
</template>
