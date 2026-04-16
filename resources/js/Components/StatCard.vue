<script setup>
import { computed } from "vue";

const props = defineProps({
	label: { type: String, required: true },
	value: { type: [Number, String], required: true },
	icon: { type: Object, default: null },
	accent: {
		type: String,
		default: "slate",
		validator: (v) =>
			["indigo", "emerald", "amber", "red", "pink", "slate"].includes(v),
	},
	secondary: { type: String, default: null },
	trend: { type: Object, default: null }, // { direction: 'up'|'down'|'flat', text: string }
	sparkline: { type: Array, default: null },
});

const accentClasses = {
	indigo: {
		border: "border-indigo-500 dark:border-indigo-400",
		iconBg: "bg-indigo-50 dark:bg-indigo-900/40",
		iconText: "text-indigo-600 dark:text-indigo-300",
		spark: "#6366f1",
	},
	emerald: {
		border: "border-emerald-500 dark:border-emerald-400",
		iconBg: "bg-emerald-50 dark:bg-emerald-900/40",
		iconText: "text-emerald-600 dark:text-emerald-300",
		spark: "#059669",
	},
	amber: {
		border: "border-amber-500 dark:border-amber-400",
		iconBg: "bg-amber-50 dark:bg-amber-900/40",
		iconText: "text-amber-600 dark:text-amber-300",
		spark: "#d97706",
	},
	red: {
		border: "border-red-500 dark:border-red-400",
		iconBg: "bg-red-50 dark:bg-red-900/40",
		iconText: "text-red-600 dark:text-red-300",
		spark: "#dc2626",
	},
	pink: {
		border: "border-pink-500 dark:border-pink-400",
		iconBg: "bg-pink-50 dark:bg-pink-900/40",
		iconText: "text-pink-600 dark:text-pink-300",
		spark: "#db2777",
	},
	slate: {
		border: "border-slate-400 dark:border-slate-500",
		iconBg: "bg-slate-100 dark:bg-slate-800",
		iconText: "text-slate-600 dark:text-slate-300",
		spark: "#64748b",
	},
};

const accentCfg = computed(() => accentClasses[props.accent] ?? accentClasses.slate);

const formattedValue = computed(() => {
	if (typeof props.value === "number") return props.value.toLocaleString();
	return props.value;
});

const sparklinePath = computed(() => {
	const series = props.sparkline;
	if (!Array.isArray(series) || series.length < 2) return null;
	const max = Math.max(...series);
	const min = Math.min(...series);
	const range = max - min || 1;
	const stepX = 100 / (series.length - 1);
	return series
		.map((v, i) => {
			const x = (i * stepX).toFixed(2);
			const y = (22 - ((v - min) / range) * 20).toFixed(2);
			return `${i === 0 ? "" : " "}${x},${y}`;
		})
		.join("");
});

const trendClasses = computed(() => {
	if (!props.trend) return "";
	switch (props.trend.direction) {
		case "up":
			return "bg-emerald-50 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300";
		case "down":
			return "bg-red-50 dark:bg-red-900/40 text-red-700 dark:text-red-300";
		default:
			return "bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300";
	}
});
</script>

<template>
	<div
		class="bg-white dark:bg-gray-800 rounded-lg shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700 p-4 border-l-4"
		:class="accentCfg.border"
	>
		<div class="flex items-start justify-between gap-2">
			<div
				class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400 font-medium"
			>
				{{ label }}
			</div>
			<div
				v-if="icon"
				class="h-8 w-8 rounded-md flex items-center justify-center flex-shrink-0"
				:class="accentCfg.iconBg"
				:aria-label="label"
			>
				<component :is="icon" class="h-4 w-4" :class="accentCfg.iconText" />
			</div>
		</div>
		<div class="mt-2 text-2xl font-bold text-gray-900 dark:text-white tabular-nums">
			{{ formattedValue }}
		</div>
		<svg
			v-if="sparklinePath"
			class="mt-2 w-full h-6"
			viewBox="0 0 100 22"
			preserveAspectRatio="none"
			aria-hidden="true"
		>
			<polyline
				fill="none"
				stroke-width="2"
				stroke-linecap="round"
				stroke-linejoin="round"
				:stroke="accentCfg.spark"
				:points="sparklinePath"
			/>
		</svg>
		<div
			v-if="secondary"
			class="mt-1 text-xs text-gray-500 dark:text-gray-400"
		>
			{{ secondary }}
		</div>
		<div v-if="trend" class="mt-2">
			<span
				class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium"
				:class="trendClasses"
			>
				<span v-if="trend.direction === 'up'">↑</span>
				<span v-else-if="trend.direction === 'down'">↓</span>
				<span v-else>→</span>
				{{ trend.text }}
			</span>
		</div>
	</div>
</template>
