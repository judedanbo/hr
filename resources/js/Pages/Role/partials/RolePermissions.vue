<script setup>
import { router, usePage } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import { debouncedWatch } from "@vueuse/core";
import NoItem from "@/Components/NoItem.vue";
import MainTable from "@/Components/MainTable.vue";
import TableHead from "@/Components/TableHead.vue";
import TableBody from "@/Components/TableBody.vue";
import RowHeader from "@/Components/RowHeader.vue";
import TableData from "@/Components/TableData.vue";
import TableRow from "@/Components/TableRow.vue";
import NewModal from "@/Components/NewModal.vue";
import {
	XMarkIcon,
	ExclamationTriangleIcon,
	MagnifyingGlassIcon,
} from "@heroicons/vue/20/solid";

defineOptions({ inheritAttrs: false });

const emit = defineEmits(["openRole"]);
const props = defineProps({
	permissions: {
		type: Object,
		required: true,
	},
	role: {
		type: Number,
		required: true,
	},
	initialSearch: {
		type: String,
		default: "",
	},
});

const page = usePage();
const canManagePermissions = computed(() =>
	page.props?.auth.permissions?.includes("assign permissions to role"),
);

const search = ref(props.initialSearch);

debouncedWatch(
	search,
	(value) => {
		router.get(
			route("role.show", { role: props.role }),
			{ permission_search: value || undefined },
			{
				preserveScroll: true,
				preserveState: true,
				only: ["permissions", "filters"],
				replace: true,
			},
		);
	},
	{ debounce: 300 },
);

const clearSearch = () => {
	search.value = "";
};

const showConfirmModal = ref(false);
const permissionToRemove = ref(null);
const isRemoving = ref(false);

const confirmRemove = (permissionName) => {
	permissionToRemove.value = permissionName;
	showConfirmModal.value = true;
};

const cancelRemove = () => {
	showConfirmModal.value = false;
	permissionToRemove.value = null;
};

const removePermission = () => {
	isRemoving.value = true;
	router.patch(
		route("role.remove.permission", { role: props.role }),
		{ permission: permissionToRemove.value },
		{
			preserveScroll: true,
			onFinish: () => {
				isRemoving.value = false;
				showConfirmModal.value = false;
				permissionToRemove.value = null;
			},
		},
	);
};

const tableCols = computed(() => {
	const cols = ["Permissions", "Users"];
	if (canManagePermissions.value) {
		cols.push("");
	}
	return cols;
});
</script>

<template>
	<section
		v-bind="$attrs"
		class="flex flex-col mt-6 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8"
	>
		<div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
			<div class="mb-3 relative">
				<div
					class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3"
				>
					<MagnifyingGlassIcon
						class="h-5 w-5 text-gray-400"
						aria-hidden="true"
					/>
				</div>
				<input
					v-model="search"
					type="text"
					placeholder="Search permissions..."
					class="block w-full rounded-md border-0 py-2 pl-10 pr-10 text-gray-900 dark:text-gray-100 dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-600 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-green-600 sm:text-sm"
				/>
				<button
					v-if="search"
					type="button"
					class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
					title="Clear search"
					@click="clearSearch"
				>
					<XMarkIcon class="h-5 w-5" />
				</button>
			</div>
			<div
				v-if="permissions?.total > 0"
				class="overflow-hidden border-b border-gray-200 rounded-md shadow-md"
			>
				<MainTable>
					<TableHead>
						<template v-for="(column, id) in tableCols" :key="id">
							<RowHeader>{{ column }}</RowHeader>
						</template>
					</TableHead>
					<TableBody>
						<template
							v-for="permission in permissions.data"
							:key="permission.id"
						>
							<TableRow>
								<TableData>
									{{ permission.name }}
								</TableData>
								<TableData>
									{{ permission.users_count }}
								</TableData>
								<TableData v-if="canManagePermissions" class="text-right">
									<button
										type="button"
										class="inline-flex items-center gap-x-1 rounded-md px-2 py-1 text-sm font-medium text-red-600 hover:text-red-800 hover:bg-red-50 dark:text-red-400 dark:hover:text-red-300 dark:hover:bg-red-900/20"
										title="Remove permission"
										@click.stop="confirmRemove(permission.name)"
									>
										<XMarkIcon class="h-4 w-4" />
										Remove
									</button>
								</TableData>
							</TableRow>
						</template>
					</TableBody>
				</MainTable>
				<slot name="pagination" />
			</div>
			<div
				v-else-if="search"
				class="py-8 text-center text-sm text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 rounded-md border border-gray-200 dark:border-gray-600"
			>
				No permissions match "{{ search }}"
			</div>
			<NoItem v-else name="Permission" />
		</div>
	</section>

	<NewModal :show="showConfirmModal" @close="cancelRemove">
		<div class="sm:flex sm:items-start">
			<div
				class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30 sm:mx-0 sm:h-10 sm:w-10"
			>
				<ExclamationTriangleIcon
					class="h-6 w-6 text-red-600 dark:text-red-400"
				/>
			</div>
			<div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
				<h3
					class="text-base font-semibold leading-6 text-gray-900 dark:text-white"
				>
					Remove Permission
				</h3>
				<div class="mt-2">
					<p class="text-sm text-gray-500 dark:text-gray-300">
						Are you sure you want to remove
						<span class="font-semibold text-gray-700 dark:text-gray-100">{{
							permissionToRemove
						}}</span>
						from this role? Users with this role will lose this permission.
					</p>
				</div>
			</div>
		</div>
		<div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse gap-3">
			<button
				type="button"
				class="inline-flex w-full justify-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:w-auto disabled:opacity-50 disabled:cursor-not-allowed"
				:disabled="isRemoving"
				@click="removePermission"
			>
				{{ isRemoving ? "Removing..." : "Remove" }}
			</button>
			<button
				type="button"
				class="mt-3 inline-flex w-full justify-center rounded-md bg-white dark:bg-gray-600 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-gray-100 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-500 hover:bg-gray-50 dark:hover:bg-gray-500 sm:mt-0 sm:w-auto"
				@click="cancelRemove"
			>
				Cancel
			</button>
		</div>
	</NewModal>
</template>
