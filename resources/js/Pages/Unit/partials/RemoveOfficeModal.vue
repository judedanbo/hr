<script setup>
import { router } from "@inertiajs/vue3";
import { ExclamationTriangleIcon } from "@heroicons/vue/24/outline";

const emit = defineEmits(["cancel", "removed"]);

const props = defineProps({
	unitId: {
		type: Number,
		required: true,
	},
	officeName: {
		type: String,
		default: "this office",
	},
});

const handleRemove = () => {
	router.delete(route("unit.office.destroy", { unit: props.unitId }), {
		preserveScroll: true,
		onSuccess: () => {
			emit("removed");
		},
	});
};
</script>

<template>
	<main class="px-8 py-8 bg-gray-100 dark:bg-gray-700">
		<div class="sm:flex sm:items-start">
			<div
				class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900 sm:mx-0 sm:h-10 sm:w-10"
			>
				<ExclamationTriangleIcon
					class="h-6 w-6 text-red-600 dark:text-red-400"
					aria-hidden="true"
				/>
			</div>
			<div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
				<h3
					class="text-lg font-semibold leading-6 text-gray-900 dark:text-gray-100"
				>
					Remove Office Assignment
				</h3>
				<div class="mt-2">
					<p class="text-sm text-gray-500 dark:text-gray-400">
						Are you sure you want to remove
						<strong class="text-gray-700 dark:text-gray-300">{{
							officeName
						}}</strong>
						from this unit? The office will not be deleted, only
						unlinked from this unit.
					</p>
				</div>
			</div>
		</div>
		<div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse gap-3">
			<button
				type="button"
				class="inline-flex w-full justify-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:w-auto"
				@click="handleRemove"
			>
				Remove
			</button>
			<button
				type="button"
				class="mt-3 inline-flex w-full justify-center rounded-md bg-white dark:bg-gray-600 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-gray-100 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-500 hover:bg-gray-50 dark:hover:bg-gray-500 sm:mt-0 sm:w-auto"
				@click="emit('cancel')"
			>
				Cancel
			</button>
		</div>
	</main>
</template>
