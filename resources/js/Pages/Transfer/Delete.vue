<script setup>
import { ref } from "vue";
import {
	Dialog,
	DialogPanel,
	DialogTitle,
	TransitionChild,
	TransitionRoot,
} from "@headlessui/vue";
import { ExclamationTriangleIcon } from "@heroicons/vue/24/outline";
const emit = defineEmits(["close", "deleteConfirmed"]);
defineProps({
	model: Object,
	staff: String,
});
</script>
<template>
	<div class="sm:flex sm:items-start">
		<div
			class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10"
		>
			<ExclamationTriangleIcon
				class="h-6 w-6 text-red-600"
				aria-hidden="true"
			/>
		</div>
		<div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
			<DialogTitle
				as="h3"
				class="text-base font-semibold leading-6 text-gray-900 dark:text-gray-50"
				>Delete Transfer
			</DialogTitle>
			<div class="mt-2 dark:text-gray-300">
				<p class="text-sm">
					You are about to delete the transfer of {{ staff }} to
					{{ model.unit_name }}.
				</p>
				<p>Are you sure you want to delete this transfer?</p>
			</div>
		</div>
	</div>
	<div
		class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6"
	>
		<button
			type="button"
			class="inline-flex w-full justify-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:ml-3 sm:w-auto"
			@click="emit('deleteConfirmed')"
		>
			Delete
		</button>
		<button
			type="button"
			class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto"
			@click="emit('close')"
			ref="cancelButtonRef"
		>
			Cancel
		</button>
	</div>
</template>
