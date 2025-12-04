<script setup>
import { computed } from "vue";
import { Link } from "@inertiajs/vue3";
import {
	CheckCircleIcon,
	ExclamationTriangleIcon,
	XCircleIcon,
	ChevronRightIcon,
} from "@heroicons/vue/24/outline";

const props = defineProps({
	items: {
		type: Array,
		required: true,
	},
});

const emit = defineEmits(["item-click"]);

const severityConfig = {
	success: {
		icon: CheckCircleIcon,
		bgColor: "bg-green-50 dark:bg-green-900/20",
		iconColor: "text-green-500 dark:text-green-400",
		textColor: "text-green-800 dark:text-green-300",
		borderColor: "border-green-200 dark:border-green-800",
	},
	warning: {
		icon: ExclamationTriangleIcon,
		bgColor: "bg-yellow-50 dark:bg-yellow-900/20",
		iconColor: "text-yellow-500 dark:text-yellow-400",
		textColor: "text-yellow-800 dark:text-yellow-300",
		borderColor: "border-yellow-200 dark:border-yellow-800",
	},
	error: {
		icon: XCircleIcon,
		bgColor: "bg-red-50 dark:bg-red-900/20",
		iconColor: "text-red-500 dark:text-red-400",
		textColor: "text-red-800 dark:text-red-300",
		borderColor: "border-red-200 dark:border-red-800",
	},
};

function getConfig(severity) {
	return severityConfig[severity] || severityConfig.success;
}

function handleClick(item) {
	if (item.route) {
		return; // Let the Link handle navigation
	}
	emit("item-click", {
		filter: item.filter,
		params: {},
		title: item.title,
	});
}
</script>

<template>
	<section>
		<h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
			Action Items
		</h2>
		<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
			<component
				v-for="item in items"
				:key="item.id"
				:is="item.route ? Link : 'div'"
				:href="item.route ? route(item.route) : undefined"
				class="relative rounded-lg border p-4 transition-all duration-200 hover:shadow-md cursor-pointer"
				:class="[
					getConfig(item.severity).bgColor,
					getConfig(item.severity).borderColor,
				]"
				@click="!item.route && handleClick(item)"
			>
				<div class="flex items-start gap-4">
					<div class="flex-shrink-0">
						<component
							:is="getConfig(item.severity).icon"
							class="h-6 w-6"
							:class="getConfig(item.severity).iconColor"
						/>
					</div>
					<div class="flex-1 min-w-0">
						<p
							class="text-sm font-semibold"
							:class="getConfig(item.severity).textColor"
						>
							{{ item.title }}
						</p>
						<p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
							{{ item.description }}
						</p>
						<p
							class="mt-2 text-2xl font-bold"
							:class="getConfig(item.severity).textColor"
						>
							{{ item.count?.toLocaleString() }}
							<span class="text-sm font-normal">
								{{ item.count === 1 ? "staff" : "staff" }}
							</span>
						</p>
					</div>
					<ChevronRightIcon
						class="h-5 w-5 text-gray-400 dark:text-gray-500 flex-shrink-0"
					/>
				</div>
			</component>
		</div>
	</section>
</template>
