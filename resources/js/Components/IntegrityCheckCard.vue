<script setup>
import { Link } from "@inertiajs/vue3";
import {
	ExclamationTriangleIcon,
	CheckCircleIcon,
	ChevronRightIcon,
} from "@heroicons/vue/24/outline";
import Badge from "@/Components/UI/Badge.vue";
import { computed } from "vue";

const props = defineProps({
	title: { type: String, required: true },
	description: { type: String, required: true },
	count: { type: Number, required: true },
	severity: {
		type: String,
		default: "success",
		validator: (value) => ["success", "warning", "error"].includes(value),
	},
	href: { type: String, required: true },
});

// The card sits on the standard surface; severity is carried by the icon and
// a badge rather than a full-bleed tint, so a page of these does not read as
// a wall of colour.
const accents = {
	success: { icon: "text-green-600 dark:text-green-400", variant: "success" },
	warning: { icon: "text-amber-600 dark:text-amber-400", variant: "warning" },
	error: { icon: "text-red-600 dark:text-red-400", variant: "danger" },
};

const accent = computed(() => accents[props.severity] ?? accents.success);
</script>

<template>
	<Link
		:href="href"
		class="group flex items-start gap-4 rounded-2xl border border-green-200/60 bg-white p-5 shadow-sm transition hover:border-green-400 hover:shadow dark:border-gray-700 dark:bg-gray-800 dark:hover:border-gray-500"
	>
		<CheckCircleIcon
			v-if="severity === 'success'"
			class="h-6 w-6 flex-shrink-0"
			:class="accent.icon"
			aria-hidden="true"
		/>
		<ExclamationTriangleIcon
			v-else
			class="h-6 w-6 flex-shrink-0"
			:class="accent.icon"
			aria-hidden="true"
		/>

		<div class="min-w-0 flex-1">
			<div class="flex items-center gap-2">
				<h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
					{{ title }}
				</h3>
				<Badge :variant="accent.variant">
					{{ severity === "success" ? "Clear" : "Needs attention" }}
				</Badge>
			</div>
			<p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
				{{ description }}
			</p>
			<p
				class="mt-3 text-2xl font-semibold tabular-nums tracking-tight text-gray-900 dark:text-gray-50"
			>
				{{ count.toLocaleString() }}
				<span class="text-sm font-normal text-gray-500 dark:text-gray-400">
					{{ count === 1 ? "issue" : "issues" }}
				</span>
			</p>
		</div>

		<ChevronRightIcon
			class="h-5 w-5 flex-shrink-0 text-gray-300 transition-colors group-hover:text-green-600 dark:text-gray-500"
			aria-hidden="true"
		/>
	</Link>
</template>
