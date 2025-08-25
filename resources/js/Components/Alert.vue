<script setup>
import { ref, onMounted, computed } from "vue";
import {
	CheckCircleIcon,
	XMarkIcon,
	InformationCircleIcon,
	ExclamationTriangleIcon,
	XCircleIcon,
} from "@heroicons/vue/20/solid";

const props = defineProps({
	alert: {
		type: String,
		default: "Alert",
	},
	type: {
		type: String,
		default: "success",
		validator: (value) =>
			["success", "error", "warning", "info"].includes(value),
	},
});

const emit = defineEmits(["close"]);
const show = ref(true);

const alertConfig = computed(() => {
	const configs = {
		success: {
			containerClass: "bg-green-50",
			textClass: "text-green-800",
			iconClass: "text-green-400",
			buttonClass:
				"bg-green-50 text-green-500 hover:bg-green-100 focus:ring-green-600 focus:ring-offset-green-50",
			icon: CheckCircleIcon,
		},
		error: {
			containerClass: "bg-red-50",
			textClass: "text-red-800",
			iconClass: "text-red-400",
			buttonClass:
				"bg-red-50 text-red-500 hover:bg-red-100 focus:ring-red-600 focus:ring-offset-red-50",
			icon: XCircleIcon,
		},
		warning: {
			containerClass: "bg-yellow-50",
			textClass: "text-yellow-800",
			iconClass: "text-yellow-400",
			buttonClass:
				"bg-yellow-50 text-yellow-500 hover:bg-yellow-100 focus:ring-yellow-600 focus:ring-offset-yellow-50",
			icon: ExclamationTriangleIcon,
		},
		info: {
			containerClass: "bg-blue-50",
			textClass: "text-blue-800",
			iconClass: "text-blue-400",
			buttonClass:
				"bg-blue-50 text-blue-500 hover:bg-blue-100 focus:ring-blue-600 focus:ring-offset-blue-50",
			icon: InformationCircleIcon,
		},
	};
	return configs[props.type] || configs.success;
});

onMounted(() => {
	setTimeout(() => {
		emit("close");
	}, 5000);
});
</script>
<template>
	<div class="rounded-md p-4" :class="alertConfig.containerClass">
		<div class="flex">
			<div class="flex-shrink-0">
				<component
					:is="alertConfig.icon"
					class="h-5 w-5"
					:class="alertConfig.iconClass"
					aria-hidden="true"
				/>
			</div>
			<div class="ml-3">
				<p class="text-sm font-medium" :class="alertConfig.textClass">
					{{ alert }}
				</p>
			</div>
			<div class="ml-auto pl-3">
				<div class="-mx-1.5 -my-1.5">
					<button
						type="button"
						class="inline-flex rounded-md p-1.5 focus:outline-none focus:ring-2 focus:ring-offset-2"
						:class="alertConfig.buttonClass"
						@click="emit('close')"
					>
						<span class="sr-only">Dismiss</span>
						<XMarkIcon class="h-5 w-5" aria-hidden="true" />
					</button>
				</div>
			</div>
		</div>
	</div>
</template>
