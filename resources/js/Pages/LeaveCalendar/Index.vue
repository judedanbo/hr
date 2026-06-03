<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, router } from "@inertiajs/vue3";
import { computed } from "vue";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import {
	parseISO,
	format,
	addMonths,
	subMonths,
	startOfMonth,
	endOfMonth,
	eachDayOfInterval,
	getDay,
} from "date-fns";

const props = defineProps({
	month: { type: String, required: true },
	entries: { type: Array, default: () => [] },
	onLeaveToday: { type: Array, default: () => [] },
	unitOptions: { type: Array, default: () => [] },
	filters: { type: Object, default: () => ({}) },
});

const monthDate = computed(() => parseISO(props.month + "-01"));
const monthLabel = computed(() => format(monthDate.value, "MMMM yyyy"));

const days = computed(() =>
	eachDayOfInterval({
		start: startOfMonth(monthDate.value),
		end: endOfMonth(monthDate.value),
	}),
);
const leadingBlanks = computed(() => getDay(startOfMonth(monthDate.value))); // 0=Sun

const weekDays = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];

const entriesForDay = (day) => {
	const d = format(day, "yyyy-MM-dd");
	return props.entries.filter((e) => e.start_date <= d && e.end_date >= d);
};

const go = (monthStr) => {
	router.get(
		route("leave-calendar.index"),
		{ month: monthStr, unit_id: props.filters.unit_id || undefined },
		{ preserveScroll: true, preserveState: true },
	);
};
const prev = () => go(format(subMonths(monthDate.value, 1), "yyyy-MM"));
const next = () => go(format(addMonths(monthDate.value, 1), "yyyy-MM"));
const filterUnit = (value) => {
	router.get(
		route("leave-calendar.index"),
		{ month: props.month, unit_id: value || undefined },
		{ preserveScroll: true, preserveState: true },
	);
};

const links = [{ name: "Leave Calendar", url: "" }];
</script>

<template>
	<MainLayout>
		<Head title="Leave Calendar" />
		<main class="max-w-7xl mx-auto sm:px-6 lg:px-8">
			<BreadCrumpVue :links="links" />

			<div class="mt-6 flex flex-wrap items-center justify-between gap-3">
				<div class="flex items-center gap-x-3">
					<button
						type="button"
						class="rounded-md border px-3 py-1 text-sm"
						@click="prev()"
					>
						‹
					</button>
					<h1 class="text-xl font-semibold text-gray-800 dark:text-gray-100">
						{{ monthLabel }}
					</h1>
					<button
						type="button"
						class="rounded-md border px-3 py-1 text-sm"
						@click="next()"
					>
						›
					</button>
				</div>
				<select
					class="rounded-md border-gray-300 text-sm dark:bg-gray-600 dark:text-gray-100"
					:value="filters.unit_id || ''"
					@change="filterUnit($event.target.value)"
				>
					<option value="">All units</option>
					<option
						v-for="opt in unitOptions"
						:key="opt.value"
						:value="opt.value"
					>
						{{ opt.label }}
					</option>
				</select>
			</div>

			<div class="mt-4 grid grid-cols-1 lg:grid-cols-4 gap-4">
				<div
					class="lg:col-span-3 rounded-md bg-white dark:bg-gray-800 p-3 shadow-sm"
				>
					<div
						class="grid grid-cols-7 gap-1 text-center text-xs font-semibold text-gray-500"
					>
						<div v-for="d in weekDays" :key="d">{{ d }}</div>
					</div>
					<div class="mt-1 grid grid-cols-7 gap-1">
						<div v-for="n in leadingBlanks" :key="'b' + n" />
						<div
							v-for="day in days"
							:key="day.toISOString()"
							class="min-h-20 rounded border border-gray-100 dark:border-gray-700 p-1 text-xs"
						>
							<div class="text-right text-gray-400">{{ format(day, "d") }}</div>
							<div
								v-for="e in entriesForDay(day)"
								:key="e.id + '-' + day.toISOString()"
								class="mt-0.5 truncate rounded px-1 text-white"
								:style="{ backgroundColor: e.color || '#16a34a' }"
								:title="e.staff + ' — ' + e.leave_type"
							>
								{{ e.staff }}
							</div>
						</div>
					</div>
				</div>

				<div class="rounded-md bg-white dark:bg-gray-800 p-4 shadow-sm">
					<h2 class="font-semibold text-gray-700 dark:text-gray-100">
						On leave today
					</h2>
					<ul class="mt-2 space-y-1 text-sm text-gray-600 dark:text-gray-300">
						<li v-for="e in onLeaveToday" :key="e.id">
							{{ e.staff }}
							<span class="text-gray-400">— {{ e.leave_type }}</span>
						</li>
						<li v-if="!onLeaveToday.length" class="text-gray-400">
							Nobody is on leave today.
						</li>
					</ul>
				</div>
			</div>
		</main>
	</MainLayout>
</template>
