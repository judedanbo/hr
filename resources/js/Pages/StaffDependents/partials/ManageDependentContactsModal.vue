<script setup>
import { ref, watch, onMounted } from "vue";
import {
	Dialog,
	DialogPanel,
	DialogTitle,
	TransitionChild,
	TransitionRoot,
} from "@headlessui/vue";
import {
	PhoneIcon,
	XMarkIcon,
	PencilSquareIcon,
	TrashIcon,
	CheckIcon,
	XCircleIcon,
} from "@heroicons/vue/24/outline";
import { router } from "@inertiajs/vue3";

const props = defineProps({
	isVisible: {
		type: Boolean,
		default: false,
	},
	personId: {
		type: Number,
		required: true,
	},
	contacts: {
		type: Array,
		default: () => [],
	},
	dependentName: {
		type: String,
		default: "",
	},
});

const emit = defineEmits(["closeModal"]);

// Contact types
const contactTypes = ref([]);

// Local state for contacts
const localContacts = ref([]);

// Add contact form
const newContact = ref({
	contact_type: "",
	contact: "",
});
const addingContact = ref(false);
const addErrors = ref({});

// Edit state
const editingContactId = ref(null);
const editForm = ref({
	contact_type: "",
	contact: "",
});
const editErrors = ref({});
const savingEdit = ref(false);

// Delete state
const deletingContactId = ref(null);

// Fetch contact types on mount
onMounted(async () => {
	const { data } = await axios.get(route("contact-type.index"));
	contactTypes.value = data;
});

// Watch for contacts prop changes
watch(
	() => props.contacts,
	(newContacts) => {
		localContacts.value = [...(newContacts || [])];
	},
	{ immediate: true },
);

// Add contact
const addContact = () => {
	addingContact.value = true;
	addErrors.value = {};

	const contactTypeValue = newContact.value.contact_type;
	const contactValue = newContact.value.contact;

	router.post(
		route("person.contact.create", { person: props.personId }),
		{
			contact_type: contactTypeValue,
			contact: contactValue,
		},
		{
			preserveScroll: true,
			onSuccess: () => {
				// Add the new contact to the local list
				const typeLabel = getContactTypeLabel(contactTypeValue);
				localContacts.value.push({
					id: Date.now(), // Temporary ID until page refresh
					type: typeLabel,
					contact_type: contactTypeValue,
					contact: contactValue,
				});
				newContact.value = { contact_type: "", contact: "" };
				addingContact.value = false;
			},
			onError: (errors) => {
				addErrors.value = errors;
				addingContact.value = false;
			},
		},
	);
};

// Start editing
const startEdit = (contact) => {
	editingContactId.value = contact.id;
	editForm.value = {
		contact_type: contact.contact_type,
		contact: contact.contact,
	};
	editErrors.value = {};
};

// Cancel editing
const cancelEdit = () => {
	editingContactId.value = null;
	editForm.value = { contact_type: "", contact: "" };
	editErrors.value = {};
};

// Save edit
const saveEdit = (contactId) => {
	savingEdit.value = true;
	editErrors.value = {};

	router.patch(
		route("person.contact.update", {
			person: props.personId,
			contact: contactId,
		}),
		{
			contact_type: editForm.value.contact_type,
			contact: editForm.value.contact,
		},
		{
			preserveScroll: true,
			onSuccess: () => {
				editingContactId.value = null;
				savingEdit.value = false;
			},
			onError: (errors) => {
				editErrors.value = errors;
				savingEdit.value = false;
			},
		},
	);
};

// Delete contact
const deleteContact = (contactId) => {
	deletingContactId.value = contactId;

	router.delete(
		route("person.contact.delete", {
			person: props.personId,
			contact: contactId,
		}),
		{
			preserveScroll: true,
			onSuccess: () => {
				deletingContactId.value = null;
			},
			onError: () => {
				deletingContactId.value = null;
			},
		},
	);
};

// Get contact type label
const getContactTypeLabel = (value) => {
	const type = contactTypes.value.find((t) => t.value === value);
	return type ? type.label : value;
};
</script>

<template>
	<TransitionRoot as="template" :show="isVisible">
		<Dialog as="div" class="relative z-50" @close="$emit('closeModal')">
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

			<div class="fixed inset-0 z-50 overflow-y-auto">
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
							class="relative transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg"
						>
							<!-- Header -->
							<div
								class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4 border-b border-gray-200 dark:border-gray-700"
							>
								<div class="flex justify-between items-center">
									<div class="flex items-center">
										<div
											class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-green-100 dark:bg-green-900"
										>
											<PhoneIcon
												class="h-6 w-6 text-green-600 dark:text-green-400"
												aria-hidden="true"
											/>
										</div>
										<div class="ml-4">
											<DialogTitle
												as="h3"
												class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-100"
											>
												Manage Contacts
											</DialogTitle>
											<p
												v-if="dependentName"
												class="text-sm text-gray-500 dark:text-gray-400"
											>
												{{ dependentName }}
											</p>
										</div>
									</div>
									<XMarkIcon
										class="h-6 w-6 text-gray-400 hover:text-gray-500 cursor-pointer"
										@click="$emit('closeModal')"
									/>
								</div>
							</div>

							<!-- Content -->
							<div class="px-4 py-4 sm:px-6 max-h-96 overflow-y-auto">
								<!-- Add Contact Form -->
								<div class="mb-4">
									<h4
										class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3"
									>
										Add New Contact
									</h4>
									<div class="grid grid-cols-2 gap-3">
										<div>
											<label
												class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1"
											>
												Type
											</label>
											<select
												v-model="newContact.contact_type"
												class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:ring-green-500 focus:border-green-500"
											>
												<option value="">Select type</option>
												<option
													v-for="type in contactTypes"
													:key="type.value"
													:value="type.value"
												>
													{{ type.label }}
												</option>
											</select>
											<p
												v-if="addErrors.contact_type"
												class="text-red-500 text-xs mt-1"
											>
												{{ addErrors.contact_type }}
											</p>
										</div>
										<div>
											<label
												class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1"
											>
												Contact
											</label>
											<input
												v-model="newContact.contact"
												type="text"
												placeholder="Enter contact"
												class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:ring-green-500 focus:border-green-500"
											/>
											<p
												v-if="addErrors.contact"
												class="text-red-500 text-xs mt-1"
											>
												{{ addErrors.contact }}
											</p>
										</div>
									</div>
									<div class="mt-3 flex justify-end">
										<button
											type="button"
											class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 disabled:bg-gray-300 dark:disabled:bg-gray-600"
											:disabled="
												addingContact ||
												!newContact.contact_type ||
												!newContact.contact
											"
											@click="addContact"
										>
											Add Contact
										</button>
									</div>
								</div>

								<!-- Existing Contacts -->
								<div
									v-if="localContacts && localContacts.length > 0"
									class="pt-4 border-t border-gray-200 dark:border-gray-700 space-y-3"
								>
									<h4
										class="text-sm font-medium text-gray-700 dark:text-gray-300"
									>
										Existing Contacts
									</h4>
									<div
										v-for="contact in localContacts"
										:key="contact.id"
										class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3"
									>
										<!-- View Mode -->
										<div
											v-if="editingContactId !== contact.id"
											class="flex items-center justify-between"
										>
											<div>
												<span
													class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide"
												>
													{{ contact.type }}
												</span>
												<p
													class="text-sm font-medium text-gray-900 dark:text-gray-100"
												>
													{{ contact.contact }}
												</p>
											</div>
											<div class="flex items-center gap-1">
												<button
													type="button"
													class="p-1.5 rounded-md text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20"
													title="Edit"
													@click="startEdit(contact)"
												>
													<PencilSquareIcon class="h-4 w-4" />
												</button>
												<button
													type="button"
													class="p-1.5 rounded-md text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20"
													title="Delete"
													:disabled="deletingContactId === contact.id"
													@click="deleteContact(contact.id)"
												>
													<TrashIcon class="h-4 w-4" />
												</button>
											</div>
										</div>

										<!-- Edit Mode -->
										<div v-else class="space-y-3">
											<div class="grid grid-cols-2 gap-3">
												<div>
													<label
														class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1"
													>
														Type
													</label>
													<select
														v-model="editForm.contact_type"
														class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 focus:ring-green-500 focus:border-green-500"
													>
														<option value="">Select type</option>
														<option
															v-for="type in contactTypes"
															:key="type.value"
															:value="type.value"
														>
															{{ type.label }}
														</option>
													</select>
													<p
														v-if="editErrors.contact_type"
														class="text-red-500 text-xs mt-1"
													>
														{{ editErrors.contact_type }}
													</p>
												</div>
												<div>
													<label
														class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1"
													>
														Contact
													</label>
													<input
														v-model="editForm.contact"
														type="text"
														class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 focus:ring-green-500 focus:border-green-500"
													/>
													<p
														v-if="editErrors.contact"
														class="text-red-500 text-xs mt-1"
													>
														{{ editErrors.contact }}
													</p>
												</div>
											</div>
											<div class="flex justify-end gap-2">
												<button
													type="button"
													class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-600 border border-gray-300 dark:border-gray-500 hover:bg-gray-50 dark:hover:bg-gray-500"
													@click="cancelEdit"
												>
													<XCircleIcon class="h-3.5 w-3.5 mr-1" />
													Cancel
												</button>
												<button
													type="button"
													class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-md text-white bg-green-600 hover:bg-green-700"
													:disabled="savingEdit"
													@click="saveEdit(contact.id)"
												>
													<CheckIcon class="h-3.5 w-3.5 mr-1" />
													Save
												</button>
											</div>
										</div>
									</div>
								</div>

								<!-- No Contacts Message -->
								<p
									v-else
									class="pt-4 border-t border-gray-200 dark:border-gray-700 text-sm text-gray-500 dark:text-gray-400 text-center py-4"
								>
									No contacts added yet.
								</p>
							</div>

							<!-- Footer -->
							<div
								class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 flex justify-end"
							>
								<button
									type="button"
									class="rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700"
									@click="$emit('closeModal')"
								>
									Close
								</button>
							</div>
						</DialogPanel>
					</TransitionChild>
				</div>
			</div>
		</Dialog>
	</TransitionRoot>
</template>
