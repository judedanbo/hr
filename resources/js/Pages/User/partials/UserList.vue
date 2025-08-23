<script setup>
import { usePage } from "@inertiajs/vue3";
import { computed } from "vue";
import NoItem from "@/Components/NoItem.vue";
import MainTable from "@/Components/MainTable.vue";
import TableHead from "@/Components/TableHead.vue";
import TableBody from "@/Components/TableBody.vue";
import RowHeader from "@/Components/RowHeader.vue";
import TableData from "@/Components/TableData.vue";
import TableRow from "@/Components/TableRow.vue";
import SubMenu from "@/Components/SubMenu.vue";

const emit = defineEmits([
	"openUser",
	"editUser",
	"deleteUser",
	"resetPassword",
]);
const props = defineProps({
	users: {
		type: Array,
		required: true,
	},
});
const page = usePage();
const permissions = computed(() => page.props.value?.auth.permissions);
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
	<section class="flex flex-col mt-6 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
		<div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
			<div
				v-if="users.length > 0"
				class="overflow-hidden border-b border-gray-200 rounded-md shadow-md"
			>
				<MainTable>
					<TableHead>
						<template v-for="(column, id) in tableCols" :key="id">
							<RowHeader>{{ column }}</RowHeader>
						</template>
					</TableHead>
					<TableBody>
						<template v-for="user in users" :key="user.id">
							<TableRow>
								<TableData>
									{{ user.name }}
								</TableData>
								<TableData>
									{{ user.email }}
								</TableData>
								<TableData>
									{{ user.verified }}
								</TableData>
								<TableData>
									{{ user.roles_count }}
								</TableData>
								<TableData>
									{{ user.permissions_count }}
								</TableData>
								<TableData class="flex justify-end">
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
										@itemClicked="(action) => subMenuClicked(action, user)"
									/>
								</TableData>
							</TableRow>
						</template>
					</TableBody>
				</MainTable>
				<slot name="pagination" />
			</div>
			<NoItem v-else name="User" />
		</div>
	</section>
</template>
