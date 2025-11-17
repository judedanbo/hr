<script setup>
import { CheckIcon, ChevronLeftIcon, ChevronRightIcon } from "@heroicons/vue/20/solid";
import { onMounted, ref } from "vue";

const props = defineProps({
	roleUsers: {
		type: Array,
		default: () => [],
	},
});

let users = ref({ data: [], current_page: 1, last_page: 1 });
let selectedUsers = ref([]);
let isLoading = ref(false);

const fetchUsers = async (page = 1) => {
	isLoading.value = true;
	try {
		const response = await axios.get(route("users.list"), { params: { page } });
		users.value = response.data;
	} catch (error) {
		console.error("Error fetching users:", error);
	} finally {
		isLoading.value = false;
	}
};

const toggleUser = (userId) => {
	const index = selectedUsers.value.indexOf(userId);
	if (index > -1) {
		selectedUsers.value.splice(index, 1);
	} else {
		selectedUsers.value.push(userId);
	}
};

const isSelected = (userId) => {
	return selectedUsers.value.includes(userId);
};

const nextPage = () => {
	if (users.value.current_page < users.value.last_page) {
		fetchUsers(users.value.current_page + 1);
	}
};

const prevPage = () => {
	if (users.value.current_page > 1) {
		fetchUsers(users.value.current_page - 1);
	}
};

onMounted(async () => {
	await fetchUsers();
});

defineExpose({
	selectedUsers,
});
</script>
<template>
	<div>
		<div class="mb-4">
			<label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
				Select Users ({{ selectedUsers.length }} selected)
			</label>
			<div v-if="isLoading" class="text-center py-4">
				<span class="text-gray-500 dark:text-gray-400">Loading users...</span>
			</div>
			<div v-else class="space-y-2 max-h-96 overflow-y-auto border border-gray-300 dark:border-gray-600 rounded-md p-3">
				<label
					v-for="user in users.data"
					:key="user.id"
					class="flex items-center space-x-3 p-2 rounded hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer"
				>
					<div class="relative flex items-center">
						<input
							:id="`user-${user.id}`"
							type="checkbox"
							:checked="isSelected(user.id)"
							@change="toggleUser(user.id)"
							class="peer h-5 w-5 rounded border-gray-300 text-green-600 focus:ring-green-500 dark:border-gray-600 dark:bg-gray-700"
						/>
						<div
							class="absolute inset-0 flex items-center justify-center pointer-events-none opacity-0 peer-checked:opacity-100"
						>
							<CheckIcon class="w-4 h-4 text-white" />
						</div>
					</div>
					<div class="flex-1">
						<div class="text-sm font-medium text-gray-900 dark:text-gray-100">
							{{ user.name }}
						</div>
						<div class="text-xs text-gray-500 dark:text-gray-400">
							{{ user.email }} • {{ user.roles_count }} roles
						</div>
					</div>
				</label>
			</div>
		</div>

		<!-- Pagination Controls -->
		<div v-if="users.last_page > 1" class="flex items-center justify-between mt-4">
			<button
				type="button"
				:disabled="users.current_page === 1"
				@click="prevPage"
				class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
			>
				<ChevronLeftIcon class="h-5 w-5 mr-1" />
				Previous
			</button>
			<span class="text-sm text-gray-700 dark:text-gray-300">
				Page {{ users.current_page }} of {{ users.last_page }}
			</span>
			<button
				type="button"
				:disabled="users.current_page === users.last_page"
				@click="nextPage"
				class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
			>
				Next
				<ChevronRightIcon class="h-5 w-5 ml-1" />
			</button>
		</div>
	</div>
</template>
