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
	open: Boolean,
});
</script>
<template>
	<TransitionRoot as="template" :show="open">
		<Dialog as="div" class="relative z-10" @close="emit('close')">
			<TransitionChild
				as="template"
				enter="ease-out duration-300"
				enter-from="opacity-0"
				enter-to="opacity-100"
				leave="ease-in duration-200"
				leave-from="opacity-100"
				leave-to="opacity-0"
			>
				<div
					class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
				/>
			</TransitionChild>

			<div class="fixed inset-0 z-10 w-screen overflow-y-auto">
				<div
					class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0"
				>
					<TransitionChild
						as="template"
						enter="ease-out duration-300"
						enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
						enter-to="opacity-100 translate-y-0 sm:scale-100"
						leave="ease-in duration-200"
						leave-from="opacity-100 translate-y-0 sm:scale-100"
						leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
					>
						<DialogPanel
							class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg"
						>
							<div
								class="bg-white dark:bg-gray-800 px-4 pb-4 pt-5 sm:p-6 sm:pb-4"
							>
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
											class="text-base font-semibold leading-6 text-gray-900 dark:text-gray-100"
											>Delete Role</DialogTitle
										>
										<div class="mt-2">
											<p class="text-sm text-gray-500 dark:text-gray-100">
												You are about to delete the {{ model.name }} role
												<br />
												<br />
												Are you sure you want to delete this role?
											</p>
										</div>
									</div>
								</div>
							</div>
							<div
								class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 dark:bg-gray-700"
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
						</DialogPanel>
					</TransitionChild>
				</div>
			</div>
		</Dialog>
	</TransitionRoot>
</template>
