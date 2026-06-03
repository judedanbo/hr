<script setup>
import NoItem from "@/Components/NoItem.vue";
import MainTable from "@/Components/MainTable.vue";
import TableHead from "@/Components/TableHead.vue";
import TableBody from "@/Components/TableBody.vue";
import RowHeader from "@/Components/RowHeader.vue";
import TableData from "@/Components/TableData.vue";
import TableRow from "@/Components/TableRow.vue";
import { router } from "@inertiajs/vue3";
import { TrashIcon } from "@heroicons/vue/20/solid";
import { ref } from "vue";

const emit = defineEmits(["openUser", "userRemoved"]);
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

const tableCols = ["User", "Permissions", "Actions"];

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
			}
		);
	}
};

const openUser = (userId) => {
	router.visit(route("user.show", { user: userId }));
};
</script>

<template>
	<section class="flex flex-col mt-6 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
		<div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
			<div
				v-if="users.total > 0"
				class="overflow-x-auto border-b border-gray-200 rounded-md shadow-md"
			>
				<MainTable>
					<TableHead>
						<template v-for="(column, id) in tableCols" :key="id">
							<RowHeader>{{ column }}</RowHeader>
						</template>
					</TableHead>
					<TableBody>
						<template v-for="user in users.data" :key="user.id">
							<TableRow>
								<TableData @click="openUser(user.id)" class="cursor-pointer">
									{{ user.name }}
								</TableData>
								<TableData @click="openUser(user.id)" class="cursor-pointer">
									{{ user.permissions_count }}
								</TableData>
								<TableData>
									<button
										@click.stop="removeUser(user.id, user.name)"
										class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
										title="Remove user from role"
									>
										<TrashIcon class="h-5 w-5" />
									</button>
								</TableData>
							</TableRow>
						</template>
					</TableBody>
				</MainTable>
				<slot name="pagination" />
			</div>
			<NoItem v-else name="Users" />
		</div>
	</section>
</template>
