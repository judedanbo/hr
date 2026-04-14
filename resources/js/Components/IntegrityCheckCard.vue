<script setup>
import { Link } from "@inertiajs/vue3";
import {
	ExclamationTriangleIcon,
	CheckCircleIcon,
	ChevronRightIcon,
} from "@heroicons/vue/24/outline";
import { computed } from "vue";

const props = defineProps({
	title: {
		type: String,
		required: true,
	},
	description: {
		type: String,
		required: true,
	},
	count: {
		type: Number,
		required: true,
	},
	severity: {
		type: String,
		default: "success",
		validator: (value) => ["success", "warning", "error"].includes(value),
	},
	href: {
		type: String,
		required: true,
	},
});

const severityClasses = computed(() => {
	const classes = {
		success: {
			bg: "bg-green-50 dark:bg-green-900/20",
			border: "border-green-200 dark:border-green-800",
			icon: "text-green-600 dark:text-green-400",
			count: "text-green-900 dark:text-green-100",
			text: "text-green-700 dark:text-green-300",
		},
		warning: {
			bg: "bg-yellow-50 dark:bg-yellow-900/20",
			border: "border-yellow-200 dark:border-yellow-800",
			icon: "text-yellow-600 dark:text-yellow-400",
			count: "text-yellow-900 dark:text-yellow-100",
			text: "text-yellow-700 dark:text-yellow-300",
		},
		error: {
			bg: "bg-red-50 dark:bg-red-900/20",
			border: "border-red-200 dark:border-red-800",
			icon: "text-red-600 dark:text-red-400",
			count: "text-red-900 dark:text-red-100",
			text: "text-red-700 dark:text-red-300",
		},
	};
	return classes[props.severity];
});
</script>

<template>
	<Link
		:href="href"
		class="block rounded-lg border-2 p-6 transition-all hover:shadow-lg"
		:class="[severityClasses.bg, severityClasses.border]"
	>
		<div class="flex items-start justify-between">
			<div class="flex items-start space-x-4">
				<div class="flex-shrink-0">
					<CheckCircleIcon
						v-if="severity === 'success'"
						class="h-8 w-8"
						:class="severityClasses.icon"
					/>
					<ExclamationTriangleIcon
						v-else
						class="h-8 w-8"
						:class="severityClasses.icon"
					/>
				</div>
				<div class="flex-1">
					<h3
						class="text-lg font-semibold"
						:class="severityClasses.count"
					>
						{{ title }}
					</h3>
					<p class="mt-1 text-sm" :class="severityClasses.text">
						{{ description }}
					</p>
					<div class="mt-3">
						<span
							class="text-3xl font-bold"
							:class="severityClasses.count"
						>
							{{ count.toLocaleString() }}
						</span>
						<span class="ml-2 text-sm" :class="severityClasses.text">
							{{
								count === 1
									? "issue found"
									: count === 0
										? "issues found"
										: "issues found"
							}}
						</span>
					</div>
				</div>
			</div>
			<ChevronRightIcon
				class="h-6 w-6 flex-shrink-0"
				:class="severityClasses.icon"
			/>
		</div>
	</Link>
</template>
