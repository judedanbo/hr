<script setup>
import DataTable from "@/Components/UI/DataTable.vue";
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

const tableCols = ["Role", "Permissions", "Users"];
</script>

<template>
	<DataTable :has-items="roles.length > 0" name="Roles">
		<template #head>
			<RowHeader v-for="(column, id) in tableCols" :key="id">
				{{ column }}
			</RowHeader>
		</template>
		<template #body>
			<TableRow
				v-for="role in roles"
				:key="role.id"
				clickable
				@click="emit('openRole', role.id)"
			>
				<TableData primary>{{ role.display_name }}</TableData>
				<TableData>{{ role.permissions_count }}</TableData>
				<TableData>{{ role.users_count }}</TableData>
			</TableRow>
		</template>
		<template #footer>
			<slot name="pagination" />
		</template>
	</DataTable>
</template>
