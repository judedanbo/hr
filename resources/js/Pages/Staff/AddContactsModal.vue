<!-- This example requires Tailwind CSS v2.0+ -->
<template>
	<TransitionRoot as="template" :show="isVisible">
		<Dialog as="div" class="relative z-10" @close="$emit('closeModal')">
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

			<div class="fixed inset-0 z-10 overflow-y-auto">
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
							<div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
								<div class="flex justify-between">
									<div class="sm:flex sm:items-center">
										<div
											class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-green-100 sm:mx-0 sm:h-10 sm:w-10"
										>
											<MegaphoneIcon
												class="h-6 w-6 text-green-900 font-bold"
												aria-hidden="true"
											/>
										</div>
										<div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
											<DialogTitle
												as="h3"
												class="text-lg font-medium leading-6 text-gray-900"
												>Add Contact</DialogTitle
											>
										</div>
									</div>

									<XMarkIcon
										title="close"
										class="w-7 h-7 p-1 text-red-400 hover:bg-gray-100 hover:font-bold rounded-full"
										@click="$emit('closeModal')"
									/>
								</div>
							</div>
							<div
								class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6"
							>
								<div
									class="relative w-full max-w-2xl h-full md:h-auto overflow-y-auto"
								>
									<!-- Modal content -->
									<form
										class="relative bg-white rounded-lg shadow dark:bg-gray-700"
										@submit.prevent="submit"
									>
										<!-- Modal body -->
										<div class="p-6 space-y-6">
											<div class="grid grid-cols-6 gap-6">
												<div class="col-span-6 sm:col-span-3">
													<label
														for="type_id"
														class="block mb-2 text-sm font-medium text-gray-900 dark:text-white"
														>Contact Type</label
													>

													<select
														id="type_id"
														class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-green-500 dark:focus:border-green-500"
														required
													>
														<option>Select one</option>
														<option value="1">Canada</option>
													</select>
													<p
														v-if="form.errors.type_id"
														class="text-red-500 text-xs pl-2 mt-1"
														v-text="form.errors.type_id"
													></p>
												</div>
												<div class="col-span-6 sm:col-span-3">
													<label
														for="contact"
														class="block mb-2 text-sm font-medium text-gray-900 dark:text-white"
														>Contact</label
													>
													<input
														id="contact"
														v-model="form.contact"
														type="text"
														name="contact"
														class="shadow-sm bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-green-600 focus:border-green-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-green-500 dark:focus:border-green-500"
														placeholder="contact"
														required=""
													/>
													<p
														v-if="form.errors.contact"
														class="text-red-500 text-xs pl-2 mt-1"
														v-text="form.errors.contact"
													></p>
												</div>
											</div>
										</div>
										<!-- Modal footer -->
										<div
											class="flex items-center py-4 space-x-2 rounded-b border-t border-gray-200 dark:border-gray-600"
										>
											<button
												type="submit"
												:disabled="form.processing"
												class="inline-flex w-full justify-center rounded-md border border-transparent bg-green-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm disabled:bg-gray-300"
											>
												<span v-if="form.processing" class="w-8"
													><ArrowPathIcon class="w-5 animate-spin"
												/></span>
												Add Contact
											</button>
										</div>
									</form>
								</div>
							</div>
						</DialogPanel>
					</TransitionChild>
				</div>
			</div>
		</Dialog>
	</TransitionRoot>
</template>

<script setup>
import { ref } from "vue";
import {
	Dialog,
	DialogPanel,
	DialogTitle,
	TransitionChild,
	TransitionRoot,
} from "@headlessui/vue";
import {
	MegaphoneIcon,
	XMarkIcon,
	ArrowPathIcon,
} from "@heroicons/vue/24/outline";
import { useForm } from "@inertiajs/inertia-vue3";

let props = defineProps({
	isVisible: {
		type: Boolean,
		default: true,
	},
	staff: { type: Object, required: true },
});

let form = useForm({
	contact: "",
	type_id: "",
});
const emit = defineEmits(["closeModal"]);

let submit = () => {
	form.post(
		route("staff.dependent.create", {
			staff: props.staff.staff_id,
		}),
		{
			onSuccess: () => {
				form.reset();
				emit("closeModal");
			},
		},
	);
};
</script>
