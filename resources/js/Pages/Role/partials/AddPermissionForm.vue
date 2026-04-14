<script setup>
import { router } from "@inertiajs/vue3";
import { onMounted, ref, computed } from "vue";
import {
	CheckIcon,
	PlusIcon,
	MagnifyingGlassIcon,
	XMarkIcon,
} from "@heroicons/vue/20/solid";

const emit = defineEmits(["formSubmitted"]);

const props = defineProps({
	role: { type: Number, required: true },
	rolePermissions: { type: Array, default: () => [] },
});

const allPermissions = ref([]);
const selectedPermissions = ref(new Set(props.rolePermissions));
const originalPermissions = new Set(props.rolePermissions);
const search = ref("");
const isLoading = ref(true);
const isSaving = ref(false);

onMounted(async () => {
	const response = await axios.get(route("permission.list"));
	allPermissions.value = response.data.map((p) => p.label);
	isLoading.value = false;
});

const filteredPermissions = computed(() => {
	if (!search.value) return allPermissions.value;
	const q = search.value.toLowerCase();
	return allPermissions.value.filter((name) =>
		name.toLowerCase().includes(q),
	);
});

const permissionState = (name) => {
	const selected = selectedPermissions.value.has(name);
	const wasAssigned = originalPermissions.has(name);
	if (selected && wasAssigned) return "assigned";
	if (selected && !wasAssigned) return "added";
	if (!selected && wasAssigned) return "removed";
	return "unselected";
};

const togglePermission = (name) => {
	const next = new Set(selectedPermissions.value);
	if (next.has(name)) {
		next.delete(name);
	} else {
		next.add(name);
	}
	selectedPermissions.value = next;
};

const counts = computed(() => {
	const selected = selectedPermissions.value;
	let assigned = 0;
	let added = 0;
	let removed = 0;
	for (const name of selected) {
		if (originalPermissions.has(name)) assigned += 1;
		else added += 1;
	}
	for (const name of originalPermissions) {
		if (!selected.has(name)) removed += 1;
	}
	return { assigned, added, removed };
});

const hasChanges = computed(
	() => counts.value.added > 0 || counts.value.removed > 0,
);

const submit = () => {
	isSaving.value = true;
	router.post(
		route("role.add.permissions", { role: props.role }),
		{
			role: props.role,
			permissions: Array.from(selectedPermissions.value),
		},
		{
			preserveScroll: true,
			onSuccess: () => {
				emit("formSubmitted");
			},
			onFinish: () => {
				isSaving.value = false;
			},
		},
	);
};

const cancel = () => {
	emit("formSubmitted");
};
</script>

<template>
	<main class="bg-gray-100 dark:bg-gray-700" @click.stop>
		<div class="px-6 py-5 border-b border-gray-200 dark:border-gray-600">
			<h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
				Manage Permissions
			</h1>
			<p class="mt-1 text-sm text-gray-500 dark:text-gray-300">
				Toggle permissions for this role. Changes are shown before saving.
			</p>
		</div>

		<div
			v-if="isLoading"
			class="flex items-center justify-center py-16"
		>
			<p class="text-gray-500 dark:text-gray-300">Loading permissions...</p>
		</div>

		<template v-else>
			<div class="px-6 pt-4">
				<div class="relative">
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
						class="block w-full rounded-md border-0 py-2 pl-10 pr-3 text-gray-900 dark:text-gray-100 dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-600 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-green-600 sm:text-sm"
					/>
				</div>

				<div class="mt-3 flex flex-wrap gap-2 text-xs">
					<span
						class="inline-flex items-center gap-x-1 rounded-md bg-green-100 dark:bg-green-900/40 px-2 py-1 font-medium text-green-800 dark:text-green-200"
					>
						<CheckIcon class="h-3.5 w-3.5" />
						{{ counts.assigned }} assigned
					</span>
					<span
						v-if="counts.added > 0"
						class="inline-flex items-center gap-x-1 rounded-md bg-indigo-100 dark:bg-indigo-900/40 px-2 py-1 font-medium text-indigo-800 dark:text-indigo-200"
					>
						<PlusIcon class="h-3.5 w-3.5" />
						{{ counts.added }} to add
					</span>
					<span
						v-if="counts.removed > 0"
						class="inline-flex items-center gap-x-1 rounded-md bg-red-100 dark:bg-red-900/40 px-2 py-1 font-medium text-red-800 dark:text-red-200"
					>
						<XMarkIcon class="h-3.5 w-3.5" />
						{{ counts.removed }} to remove
					</span>
				</div>
			</div>

			<div class="px-6 py-4">
				<ul
					class="max-h-96 overflow-y-auto rounded-md border border-gray-200 dark:border-gray-600 divide-y divide-gray-200 dark:divide-gray-600 bg-white dark:bg-gray-800"
				>
					<li
						v-for="name in filteredPermissions"
						:key="name"
						:class="[
							'flex items-center justify-between px-4 py-2.5 cursor-pointer transition-colors',
							{
								'bg-green-50 dark:bg-green-900/20 hover:bg-green-100 dark:hover:bg-green-900/30':
									permissionState(name) === 'assigned',
								'bg-indigo-50 dark:bg-indigo-900/20 hover:bg-indigo-100 dark:hover:bg-indigo-900/30':
									permissionState(name) === 'added',
								'bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/30 line-through':
									permissionState(name) === 'removed',
								'hover:bg-gray-50 dark:hover:bg-gray-700':
									permissionState(name) === 'unselected',
							},
						]"
						@click="togglePermission(name)"
					>
						<span
							:class="[
								'text-sm font-medium',
								{
									'text-green-900 dark:text-green-100':
										permissionState(name) === 'assigned',
									'text-indigo-900 dark:text-indigo-100':
										permissionState(name) === 'added',
									'text-red-900 dark:text-red-200':
										permissionState(name) === 'removed',
									'text-gray-700 dark:text-gray-200':
										permissionState(name) === 'unselected',
								},
							]"
						>
							{{ name }}
						</span>
						<span class="flex items-center">
							<span
								v-if="permissionState(name) === 'assigned'"
								class="inline-flex items-center gap-x-1 rounded-full bg-green-600 px-2 py-0.5 text-xs font-medium text-white"
							>
								<CheckIcon class="h-3.5 w-3.5" />
								Assigned
							</span>
							<span
								v-else-if="permissionState(name) === 'added'"
								class="inline-flex items-center gap-x-1 rounded-full bg-indigo-600 px-2 py-0.5 text-xs font-medium text-white"
							>
								<PlusIcon class="h-3.5 w-3.5" />
								New
							</span>
							<span
								v-else-if="permissionState(name) === 'removed'"
								class="inline-flex items-center gap-x-1 rounded-full bg-red-600 px-2 py-0.5 text-xs font-medium text-white"
							>
								<XMarkIcon class="h-3.5 w-3.5" />
								Remove
							</span>
							<span
								v-else
								class="h-5 w-5 rounded-full border-2 border-gray-300 dark:border-gray-500"
							/>
						</span>
					</li>
					<li
						v-if="filteredPermissions.length === 0"
						class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400"
					>
						No permissions match "{{ search }}"
					</li>
				</ul>
			</div>

			<div
				class="px-6 py-4 bg-gray-50 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-600 flex justify-end gap-3"
			>
				<button
					type="button"
					class="inline-flex justify-center rounded-md bg-white dark:bg-gray-600 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-gray-100 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-500 hover:bg-gray-50 dark:hover:bg-gray-500"
					@click="cancel"
				>
					Cancel
				</button>
				<button
					type="button"
					class="inline-flex justify-center rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 disabled:opacity-50 disabled:cursor-not-allowed"
					:disabled="!hasChanges || isSaving"
					@click="submit"
				>
					{{ isSaving ? "Saving..." : "Save Changes" }}
				</button>
			</div>
		</template>
	</main>
</template>
