<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from "vue";
import { Link, router } from "@inertiajs/vue3";
import { Popover, PopoverButton, PopoverPanel } from "@headlessui/vue";
import {
	BellIcon,
	CameraIcon,
	CheckCircleIcon,
	XCircleIcon,
	AcademicCapIcon,
	XMarkIcon,
} from "@heroicons/vue/24/outline";
import { formatDistanceToNow, parseISO } from "date-fns";
import axios from "axios";

const POLL_INTERVAL_MS = 45000;
const ICONS = {
	camera: CameraIcon,
	"check-circle": CheckCircleIcon,
	"x-circle": XCircleIcon,
	"academic-cap": AcademicCapIcon,
	bell: BellIcon,
};

const unreadCount = ref(0);
const items = ref([]);
const loading = ref(false);
let pollTimer = null;

const badgeLabel = computed(() => {
	if (unreadCount.value <= 0) {
		return "";
	}
	return unreadCount.value > 9 ? "9+" : String(unreadCount.value);
});

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

async function fetchRecent() {
	if (loading.value) {
		return;
	}
	loading.value = true;
	try {
		const { data } = await axios.get(route("notifications.recent"));
		unreadCount.value = data.unread_count ?? 0;
		items.value = data.items ?? [];
	} catch (e) {
		// Swallow transient errors — next poll will retry.
	} finally {
		loading.value = false;
	}
}

function startPolling() {
	stopPolling();
	pollTimer = setInterval(() => {
		if (!document.hidden) {
			fetchRecent();
		}
	}, POLL_INTERVAL_MS);
}

function stopPolling() {
	if (pollTimer) {
		clearInterval(pollTimer);
		pollTimer = null;
	}
}

function onVisibilityChange() {
	if (!document.hidden) {
		fetchRecent();
	}
}

async function openItem(item) {
	// Optimistic: mark locally, navigate, persist.
	if (!item.read_at) {
		item.read_at = new Date().toISOString();
		unreadCount.value = Math.max(0, unreadCount.value - 1);
		axios
			.post(route("notifications.read", { notification: item.id }))
			.catch(() => {});
	}
	if (item.url) {
		router.visit(item.url);
	}
}

async function dismiss(item) {
	const wasUnread = !item.read_at;
	items.value = items.value.filter((i) => i.id !== item.id);
	if (wasUnread) {
		unreadCount.value = Math.max(0, unreadCount.value - 1);
	}
	try {
		await axios.delete(
			route("notifications.destroy", { notification: item.id }),
		);
	} catch (e) {
		// If it failed, refetch to resync.
		fetchRecent();
	}
}

async function markAllRead() {
	items.value = items.value.map((i) => ({
		...i,
		read_at: i.read_at || new Date().toISOString(),
	}));
	unreadCount.value = 0;
	try {
		await axios.post(route("notifications.read-all"));
	} catch (e) {
		fetchRecent();
	}
}

onMounted(() => {
	fetchRecent();
	startPolling();
	document.addEventListener("visibilitychange", onVisibilityChange);
});

onBeforeUnmount(() => {
	stopPolling();
	document.removeEventListener("visibilitychange", onVisibilityChange);
});
</script>

<template>
	<Popover as="div" class="relative">
		<PopoverButton
			class="relative -m-2.5 flex items-center p-2.5 text-gray-700 hover:text-gray-500 focus:outline-none dark:text-gray-50 dark:hover:text-gray-200"
		>
			<span class="sr-only">View notifications</span>
			<BellIcon class="h-6 w-6" aria-hidden="true" />
			<span
				v-if="badgeLabel"
				class="absolute top-1 right-1 inline-flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-semibold leading-none text-white"
				>{{ badgeLabel }}</span
			>
		</PopoverButton>

		<transition
			enter-active-class="transition ease-out duration-100"
			enter-from-class="transform opacity-0 scale-95"
			enter-to-class="transform opacity-100 scale-100"
			leave-active-class="transition ease-in duration-75"
			leave-from-class="transform opacity-100 scale-100"
			leave-to-class="transform opacity-0 scale-95"
		>
			<PopoverPanel
				class="absolute right-0 z-20 mt-2.5 w-96 origin-top-right overflow-hidden rounded-md bg-white shadow-lg ring-1 ring-gray-900/5 focus:outline-none dark:bg-gray-800 dark:ring-gray-700"
			>
				<div
					class="flex items-center justify-between border-b border-gray-100 px-4 py-3 dark:border-gray-700"
				>
					<h3
						class="text-sm font-semibold text-gray-900 dark:text-gray-50"
					>
						Notifications
					</h3>
					<button
						type="button"
						class="text-xs font-medium text-indigo-600 hover:text-indigo-500 disabled:opacity-50 dark:text-indigo-400"
						:disabled="unreadCount === 0"
						@click="markAllRead"
					>
						Mark all as read
					</button>
				</div>

				<div
					v-if="items.length === 0"
					class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400"
				>
					You're all caught up.
				</div>

				<ul
					v-else
					class="max-h-96 divide-y divide-gray-100 overflow-y-auto dark:divide-gray-700"
				>
					<li
						v-for="item in items"
						:key="item.id"
						class="group flex gap-3 px-4 py-3 transition hover:bg-gray-50 dark:hover:bg-gray-700"
						:class="
							item.read_at
								? ''
								: 'bg-indigo-50/40 dark:bg-indigo-900/20'
						"
					>
						<button
							type="button"
							class="flex flex-1 items-start gap-3 text-left"
							@click="openItem(item)"
						>
							<component
								:is="iconFor(item.icon)"
								class="mt-0.5 h-5 w-5 flex-shrink-0 text-gray-500 dark:text-gray-300"
								aria-hidden="true"
							/>
							<div class="min-w-0 flex-1">
								<p
									class="truncate text-sm text-gray-900 dark:text-gray-50"
									:class="
										item.read_at
											? 'font-normal'
											: 'font-semibold'
									"
								>
									{{ item.title }}
								</p>
								<p
									v-if="item.body"
									class="mt-0.5 line-clamp-2 text-xs text-gray-600 dark:text-gray-300"
								>
									{{ item.body }}
								</p>
								<p
									class="mt-1 text-[11px] text-gray-500 dark:text-gray-400"
								>
									{{ relativeTime(item.created_at) }}
								</p>
							</div>
						</button>
						<button
							type="button"
							class="invisible flex-shrink-0 self-start rounded p-1 text-gray-400 hover:bg-gray-200 hover:text-gray-600 group-hover:visible dark:hover:bg-gray-600"
							aria-label="Dismiss"
							@click.stop="dismiss(item)"
						>
							<XMarkIcon class="h-4 w-4" aria-hidden="true" />
						</button>
					</li>
				</ul>

				<div
					class="border-t border-gray-100 px-4 py-2 text-center dark:border-gray-700"
				>
					<Link
						:href="route('notifications.index')"
						class="text-xs font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400"
						>View all notifications</Link
					>
				</div>
			</PopoverPanel>
		</transition>
	</Popover>
</template>
