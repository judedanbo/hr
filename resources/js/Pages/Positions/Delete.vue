<script setup>
import { router } from "@inertiajs/vue3";
import { ExclamationTriangleIcon } from "@heroicons/vue/24/outline";

const emit = defineEmits(["cancelDelete", "deletedPosition"]);
</script>

<template>
	<main class="px-8 py-8 bg-gray-100 dark:bg-gray-700">
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
				<div
					as="h3"
					class="text-base font-semibold leading-6 text-gray-900 dark:text-gray-50"
				>
					Delete Position
				</div>
				<div class="mt-2">
					<p class="text-sm text-gray-500 dark:text-gray-200">
						Are you sure you want to delete the position?
					</p>
				</div>
			</div>
		</div>
		<div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
			<button
				type="button"
				class="inline-flex w-full justify-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:ml-3 sm:w-auto"
				@click="emit('deletedPosition')"
			>
				Delete
			</button>
			<button
				type="button"
				class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto"
				@click="emit('cancelDelete')"
			>
				Cancel
			</button>
		</div>
	</main>
</template>
