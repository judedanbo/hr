<script setup>
import DataTable from "@/Components/UI/DataTable.vue";
import RowHeader from "@/Components/RowHeader.vue";
import TableData from "@/Components/TableData.vue";
import TableRow from "@/Components/TableRow.vue";
import { router } from "@inertiajs/vue3";
import { TrashIcon } from "@heroicons/vue/20/solid";

const emit = defineEmits(["userRemoved"]);
const props = defineProps({
	users: {
		type: Object,
		required: true,
	},
	role: {
		type: Number,
		required: true,
	},
});

const tableCols = ["User", "Permissions"];

const removeUser = (userId, userName) => {
	if (confirm(`Are you sure you want to remove ${userName} from this role?`)) {
		router.patch(
			route("role.remove.user", { role: props.role }),
			{ user: userId },
			{
				preserveScroll: true,
				onSuccess: () => {
					emit("userRemoved");
				},
				onError: (errors) => {
					console.error("Error removing user:", errors);
				},
			},
		);
	}
};

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
			<RowHeader align="right">Actions</RowHeader>
		</template>
		<template #body>
			<TableRow
				v-for="user in users.data"
				:key="user.id"
				clickable
				@click="openUser(user.id)"
			>
				<TableData primary>{{ user.name }}</TableData>
				<TableData>{{ user.permissions_count }}</TableData>
				<TableData align="right" @click.stop>
					<button
						type="button"
						class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
						title="Remove user from role"
						@click="removeUser(user.id, user.name)"
					>
						<TrashIcon class="h-5 w-5" />
					</button>
				</TableData>
			</TableRow>
		</template>
		<template #footer>
			<slot name="pagination" />
		</template>
	</DataTable>
</template>
