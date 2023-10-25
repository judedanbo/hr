<!-- This example requires Tailwind CSS v2.0+ -->
<template>
	<TransitionRoot as="template" :show="isVisible">
		<Dialog @close="$emit('closeModal')" as="div" class="relative z-10">
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
										@click="$emit('closeModal')"
										title="close"
										class="w-7 h-7 p-1 text-red-400 hover:bg-gray-100 hover:font-bold rounded-full"
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
										@submit.prevent="submit"
										class="relative bg-white rounded-lg shadow dark:bg-gray-700"
									>
										<!-- Modal body -->
										<div class="p-6 space-y-6">
											<div class="grid grid-cols-6 gap-6">
												<div class="col-span-6">
													<label
														for="address_line_1"
														class="block mb-2 text-sm font-medium text-gray-900 dark:text-white"
														>Address Line 1</label
													>
													<input
														v-model="form.address_line_1"
														type="text"
														name="address_line_1"
														id="address_line_1"
														class="shadow-sm bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-green-600 focus:border-green-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-green-500 dark:focus:border-green-500"
														placeholder="address line 1"
														required=""
													/>
													<p
														v-if="form.errors.address_line_1"
														v-text="form.errors.address_line_1"
														class="text-red-500 text-xs pl-2 mt-1"
													></p>
												</div>
												<div class="col-span-6">
													<label
														for="address_line_2"
														class="block mb-2 text-sm font-medium text-gray-900 dark:text-white"
														>Address Line 2</label
													>
													<input
														v-model="form.address_line_2"
														type="text"
														name="address_line_2"
														id="address_line_2"
														class="shadow-sm bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-green-600 focus:border-green-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-green-500 dark:focus:border-green-500"
														placeholder="address line 2"
														required=""
													/>
													<p
														v-if="form.errors.address_line_2"
														v-text="form.errors.address_line_2"
														class="text-red-500 text-xs pl-2 mt-1"
													></p>
												</div>
												<div class="col-span-6 sm:col-span-3">
													<label
														for="city"
														class="block mb-2 text-sm font-medium text-gray-900 dark:text-white"
														>City</label
													>
													<input
														v-model="form.city"
														type="text"
														name="city"
														id="city"
														class="shadow-sm bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-green-600 focus:border-green-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-green-500 dark:focus:border-green-500"
														placeholder="city"
														required="false"
													/>
													<p
														v-if="form.errors.city"
														v-text="form.errors.city"
														class="text-red-500 text-xs pl-2 mt-1"
													></p>
												</div>
												<div class="col-span-6 sm:col-span-3">
													<label
														for="region"
														class="block mb-2 text-sm font-medium text-gray-900 dark:text-white"
														>Region</label
													>
													<input
														v-model="form.region"
														type="text"
														name="region"
														id="region"
														class="shadow-sm bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-green-600 focus:border-green-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-green-500 dark:focus:border-green-500"
														placeholder="region"
														required=""
													/>
													<p
														v-if="form.errors.region"
														v-text="form.errors.region"
														class="text-red-500 text-xs pl-2 mt-1"
													></p>
												</div>
												<div class="col-span-6 sm:col-span-3">
													<label
														for="country"
														class="block mb-2 text-sm font-medium text-gray-900 dark:text-white"
														>Country</label
													>
													<input
														v-model="form.country"
														type="text"
														name="country"
														id="country"
														class="shadow-sm bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-green-600 focus:border-green-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-green-500 dark:focus:border-green-500"
														placeholder="country"
														required=""
													/>
													<p
														v-if="form.errors.country"
														v-text="form.errors.country"
														class="text-red-500 text-xs pl-2 mt-1"
													></p>
												</div>
												<div class="col-span-6 sm:col-span-3">
													<label
														for="post_code"
														class="block mb-2 text-sm font-medium text-gray-900 dark:text-white"
														>Post Code</label
													>
													<input
														v-model="form.post_code"
														type="text"
														name="post_code"
														id="post_code"
														class="shadow-sm bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-green-600 focus:border-green-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-green-500 dark:focus:border-green-500"
														placeholder="post code"
														required=""
													/>
													<p
														v-if="form.errors.post_code"
														v-text="form.errors.post_code"
														class="text-red-500 text-xs pl-2 mt-1"
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
												Add Address
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
	person_id: Number,
});

let form = useForm({
	address_line_1: "",
	address_line_2: "",
	city: "",
	region: "",
	country: "",
	post_code: "",
});
const emit = defineEmits(["closeModal"]);

let submit = () => {
	form.post(
		route("person.address.create", {
			person: props.person_id,
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
