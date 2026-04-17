<script setup>
import { ref, computed, watch } from "vue";
import { Head, router } from "@inertiajs/vue3";
import { Listbox, ListboxButton, ListboxOption, ListboxOptions } from "@headlessui/vue";
import {
	BellIcon,
	CameraIcon,
	CheckCircleIcon,
	XCircleIcon,
	AcademicCapIcon,
	XMarkIcon,
	CheckIcon,
	ChevronUpDownIcon,
} from "@heroicons/vue/24/outline";
import { formatDistanceToNow, parseISO } from "date-fns";
import axios from "axios";
import NewAuthenticated from "@/Layouts/NewAuthenticated.vue";
import Pagination from "@/Components/Pagination.vue";

const props = defineProps({
	notifications: { type: Object, required: true },
	filters: { type: Object, required: true },
	types: { type: Array, default: () => [] },
	unread_count: { type: Number, default: 0 },
});

const ICONS = {
	camera: CameraIcon,
	"check-circle": CheckCircleIcon,
	"x-circle": XCircleIcon,
	"academic-cap": AcademicCapIcon,
	bell: BellIcon,
};

const STATUS_OPTIONS = [
	{ value: "all", label: "All" },
	{ value: "unread", label: "Unread" },
	{ value: "read", label: "Read" },
];

const status = ref(props.filters.status || "all");
const selectedType = ref(props.filters.type || null);

const typeOptions = computed(() => [
	{ value: null, label: "All types" },
	...props.types,
]);

const selectedTypeOption = computed(
	() =>
		typeOptions.value.find((o) => o.value === selectedType.value) ??
		typeOptions.value[0],
);

function applyFilters() {
	router.get(
		route("notifications.index"),
		{
			status: status.value,
			type: selectedType.value ?? undefined,
		},
		{ preserveScroll: true, preserveState: true, replace: true },
	);
}

watch(status, applyFilters);
watch(selectedType, applyFilters);

function iconFor(name) {
	return ICONS[name] || BellIcon;
}

function relativeTime(iso) {
	if (!iso) {
		return "";
	}
	try {
		return formatDistanceToNow(parseISO(iso), { addSuffix: true });
	} catch (e) {
		return "";
	}
}

function openItem(item) {
	if (!item.read_at) {
		axios
			.post(route("notifications.read", { notification: item.id }))
			.catch(() => {});
	}
	if (item.url) {
		router.visit(item.url);
	}
}

async function toggleRead(item) {
	if (item.read_at) {
		// No "mark unread" backend; simulate with a reload after deleting read_at? Not supported — skip.
		return;
	}
	try {
		await axios.post(route("notifications.read", { notification: item.id }));
		router.reload({ preserveScroll: true });
	} catch (e) {
		// ignore
	}
}

async function destroy(item) {
	try {
		await axios.delete(
			route("notifications.destroy", { notification: item.id }),
		);
		router.reload({ preserveScroll: true });
	} catch (e) {
		// ignore
	}
}

async function markAllRead() {
	try {
		await axios.post(route("notifications.read-all"));
		router.reload({ preserveScroll: true });
	} catch (e) {
		// ignore
	}
}
</script>

<template>
	<Head title="Notifications" />
	<NewAuthenticated>
		<div class="py-6">
			<div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
				<div class="flex items-center justify-between">
					<div>
						<h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-50">
							Notifications
						</h1>
						<p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
							{{ unread_count }} unread
						</p>
					</div>
					<button
						type="button"
						class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50"
						:disabled="unread_count === 0"
						@click="markAllRead"
					>
						Mark all as read
					</button>
				</div>

				<!-- Filters -->
				<div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center">
					<div class="inline-flex rounded-md shadow-sm">
						<button
							v-for="(opt, idx) in STATUS_OPTIONS"
							:key="opt.value"
							type="button"
							class="border border-gray-300 px-3 py-1.5 text-sm font-medium dark:border-gray-600"
							:class="[
								status === opt.value
									? 'bg-indigo-600 text-white'
									: 'bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-50',
								idx === 0 ? 'rounded-l-md' : '',
								idx === STATUS_OPTIONS.length - 1 ? 'rounded-r-md' : '',
								idx > 0 ? '-ml-px' : '',
							]"
							@click="status = opt.value"
						>
							{{ opt.label }}
						</button>
					</div>

					<div class="relative w-full sm:w-64">
						<Listbox v-model="selectedType">
							<ListboxButton
								class="relative w-full cursor-pointer rounded-md border border-gray-300 bg-white py-1.5 pl-3 pr-10 text-left text-sm text-gray-900 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-50"
							>
								<span class="block truncate">{{
									selectedTypeOption.label
								}}</span>
								<span
									class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2"
								>
									<ChevronUpDownIcon
										class="h-5 w-5 text-gray-400"
										aria-hidden="true"
									/>
								</span>
							</ListboxButton>
							<ListboxOptions
								class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-sm shadow-lg ring-1 ring-black/5 focus:outline-none dark:bg-gray-700"
							>
								<ListboxOption
									v-for="opt in typeOptions"
									:key="opt.value ?? 'all'"
									v-slot="{ active, selected }"
									:value="opt.value"
								>
									<div
										class="flex cursor-pointer items-center gap-2 px-3 py-1.5"
										:class="
											active
												? 'bg-indigo-50 text-indigo-700 dark:bg-gray-600'
												: 'text-gray-900 dark:text-gray-50'
										"
									>
										<CheckIcon
											v-if="selected"
											class="h-4 w-4"
											aria-hidden="true"
										/>
										<span v-else class="h-4 w-4" />
										<span>{{ opt.label }}</span>
									</div>
								</ListboxOption>
							</ListboxOptions>
						</Listbox>
					</div>
				</div>

				<!-- List -->
				<div
					class="mt-4 overflow-hidden rounded-md border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800"
				>
					<div
						v-if="notifications.data.length === 0"
						class="px-4 py-10 text-center text-sm text-gray-500 dark:text-gray-400"
					>
						You're all caught up.
					</div>
					<ul
						v-else
						class="divide-y divide-gray-100 dark:divide-gray-700"
					>
						<li
							v-for="item in notifications.data"
							:key="item.id"
							class="group flex gap-3 px-4 py-4 hover:bg-gray-50 dark:hover:bg-gray-700"
							:class="[
								item.read_at
									? ''
									: 'border-l-4 border-indigo-500 bg-indigo-50/40 dark:bg-indigo-900/20',
							]"
						>
							<component
								:is="iconFor(item.icon)"
								class="mt-0.5 h-6 w-6 flex-shrink-0 text-gray-500 dark:text-gray-300"
								aria-hidden="true"
							/>
							<button
								type="button"
								class="flex flex-1 flex-col text-left"
								@click="openItem(item)"
							>
								<span
									class="text-sm text-gray-900 dark:text-gray-50"
									:class="
										item.read_at
											? 'font-normal'
											: 'font-semibold'
									"
									>{{ item.title }}</span
								>
								<span
									v-if="item.body"
									class="mt-0.5 text-sm text-gray-600 dark:text-gray-300"
									>{{ item.body }}</span
								>
								<span
									class="mt-1 text-xs text-gray-500 dark:text-gray-400"
									>{{ relativeTime(item.created_at) }}</span
								>
							</button>
							<div class="flex flex-shrink-0 items-start gap-1">
								<button
									v-if="!item.read_at"
									type="button"
									class="rounded p-1 text-gray-400 hover:bg-gray-200 hover:text-gray-700 dark:hover:bg-gray-600"
									aria-label="Mark as read"
									@click="toggleRead(item)"
								>
									<CheckIcon class="h-4 w-4" />
								</button>
								<button
									type="button"
									class="rounded p-1 text-gray-400 hover:bg-gray-200 hover:text-gray-700 dark:hover:bg-gray-600"
									aria-label="Delete"
									@click="destroy(item)"
								>
									<XMarkIcon class="h-4 w-4" />
								</button>
							</div>
						</li>
					</ul>

					<Pagination
						v-if="notifications.data.length > 0"
						:navigation="notifications"
					/>
				</div>
			</div>
		</div>
	</NewAuthenticated>
</template>
