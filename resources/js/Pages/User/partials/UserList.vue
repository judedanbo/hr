<script setup>
import { usePage } from "@inertiajs/vue3";
import { computed } from "vue";
import DataTable from "@/Components/UI/DataTable.vue";
import RowHeader from "@/Components/RowHeader.vue";
import TableData from "@/Components/TableData.vue";
import TableRow from "@/Components/TableRow.vue";
import SubMenu from "@/Components/SubMenu.vue";

const emit = defineEmits([
	"openUser",
	"editUser",
	"deleteUser",
	"resetPassword",
	"associateStaff",
]);
const props = defineProps({
	users: {
		type: Array,
		required: true,
	},
	canAssociateStaff: {
		type: Boolean,
		default: false,
	},
});
const page = usePage();
const permissions = computed(() => page.props?.auth.permissions);
const subMenuClicked = (action, model) => {
	if (action == "Open") {
		// @click="emit('openUser', user.id)"
		emit("openUser", model.id);
	}
	if (action == "Edit") {
		emit("editUser", model);
	}
	if (action == "Delete") {
		emit("deleteUser", model);
	}
	if (action == "Reset Password") {
		emit("resetPassword", model.id);
	}
};

const tableCols = [
	"Name",
	"Email Address",
	"Verified",
	"Roles",
	"Permissions",
	"Action",
];
</script>

<template>
	<DataTable :has-items="users.length > 0" name="Users">
		<template #head>
			<RowHeader
				v-for="(column, id) in tableCols"
				:key="id"
				:align="column === 'Action' ? 'right' : 'left'"
			>
				{{ column }}
			</RowHeader>
		</template>
		<template #body>
			<TableRow
				v-for="user in users"
				:key="user.id"
				clickable
				@click="emit('openUser', user.id)"
			>
				<TableData primary>{{ user.name }}</TableData>
				<TableData>{{ user.email }}</TableData>
				<TableData>{{ user.verified }}</TableData>
				<TableData>{{ user.roles_count }}</TableData>
				<TableData>{{ user.permissions_count }}</TableData>
				<TableData class="flex items-center justify-end gap-2" @click.stop>
					<button
						v-if="canAssociateStaff"
						type="button"
						class="text-xs font-medium text-green-700 hover:underline dark:text-green-300"
						@click="emit('associateStaff', user.id)"
					>
						{{ user.person_id ? "Change staff" : "Associate staff" }}
					</button>
					<SubMenu
						v-if="
							permissions?.includes('update staff') ||
							permissions?.includes('delete staff')
						"
						:can-edit="permissions?.includes('update staff')"
						:can-delete="permissions?.includes('delete staff')"
						:can-view="permissions?.includes('view staff')"
						:can-change-user-password="
							permissions?.includes('reset user password')
						"
						:items="['Open', 'Reset Password', 'Edit', 'Delete']"
						@item-clicked="(action) => subMenuClicked(action, user)"
					/>
				</TableData>
			</TableRow>
		</template>
		<template #footer>
			<slot name="pagination" />
		</template>
	</DataTable>
</template>
