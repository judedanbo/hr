<script setup>
import DataTable from "@/Components/UI/DataTable.vue";
import RowHeader from "@/Components/RowHeader.vue";
import TableData from "@/Components/TableData.vue";
import TableRow from "@/Components/TableRow.vue";

const emit = defineEmits(["openPermission"]);
const props = defineProps({
	permissions: {
		type: Array,
		required: true,
	},
});

const tableCols = ["Permission", "Roles", "Users"];
</script>

<template>
	<DataTable :has-items="permissions.length > 0" name="Permissions">
		<template #head>
			<RowHeader v-for="(column, id) in tableCols" :key="id">
				{{ column }}
			</RowHeader>
		</template>
		<template #body>
			<TableRow
				v-for="permission in permissions"
				:key="permission.id"
				clickable
				@click="emit('openPermission', permission.id)"
			>
				<TableData primary>{{ permission.display_name }}</TableData>
				<TableData>{{ permission.roles_count }}</TableData>
				<TableData>{{ permission.users_count }}</TableData>
			</TableRow>
		</template>
		<template #footer>
			<slot name="pagination" />
		</template>
	</DataTable>
</template>
