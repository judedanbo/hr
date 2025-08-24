<script setup>
import Modal from "@/Components/NewModal.vue";
import { ref, computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import { useToggle } from "@vueuse/core";
import AddAddress from "../Person/partials/AddAddress.vue";
import AddContact from "../Person/partials/AddContact.vue";
import EditContact from "../Person/partials/EditContact.vue";
import SubMenu from "@/Components/SubMenu.vue";

defineProps({
	address: { type: Object, required: true },
	contacts: { type: Array, required: true },
	person: { type: Number, required: true },
});
const page = usePage();
const permissions = computed(() => page.props?.auth.permissions);

const emit = defineEmits(["editContact", "deleteDependent"]);
const contactModel = ref(null);
const subMenuClicked = (action, model) => {
	if (action == "Edit") {
		contactModel.value = model;
		toggleEditContactModal();
		// emit("editContact", model);
	}
	if (action == "Delete") {
		emit("deleteDependent", model);
	}
};

let openAddressModal = ref(false);
let toggleAddressModal = useToggle(openAddressModal);

let openContactModal = ref(false);
let toggleContactModal = useToggle(openContactModal);

let openEditContactModal = ref(false);
let toggleEditContactModal = useToggle(openEditContactModal);
</script>
<template>
	<!-- contact History -->
	<!-- {{ contacts }} -->
	<main class="w-full">
		<h2 class="sr-only">Staff Contact Information</h2>
		<div
			class="md:rounded-lg bg-gray-50 dark:bg-gray-500 shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-500/80"
		>
			<dl class="flex flex-wrap">
				<div class="flex-auto pl-6 pt-6">
					<dt
						class="text-md tracking-wide font-semibold leading-6 text-gray-900 dark:text-gray-100"
					>
						Staff Address
					</dt>
				</div>
				<div class="flex-none self-end px-6 pt-4">
					<button
						class="rounded-md bg-green-50 dark:bg-gray-400 px-2 py-1 text-xs font-medium text-green-600 dark:text-gray-50 ring-1 ring-inset ring-green-600/20 dark:ring-gray-500"
						@click="toggleAddressModal()"
					>
						Change address
					</button>
				</div>

				<div class="-mx-4 mt-8 flow-root sm:mx-0 w-full px-4">
					<div v-if="address">
						<dd class="text-sm leading-6 text-gray-500 dark:text-gray-50">
							{{ address.address_line_1 ?? "Address line 1 Not Specified" }}
						</dd>
						<dd class="text-sm leading-6 text-gray-500 dark:text-gray-50">
							{{ address.address_line_2 ?? "Address line 2 Not Specified" }}
						</dd>
						<dd class="text-sm leading-6 text-gray-500 dark:text-gray-50">
							{{ address.city ?? "City Not Specified" }}
						</dd>
						<dd class="text-sm leading-6 text-gray-500 dark:text-gray-50">
							{{ address.region ?? "Region Not Specified" }}
						</dd>
						<dd class="text-sm leading-6 text-gray-500 dark:text-gray-50">
							{{ address.country ?? "Country Not Specified" }}
						</dd>
						<dd class="text-sm leading-6 text-gray-500 dark:text-gray-50">
							{{ address.post_code ?? "Post Code Not Specified" }}
						</dd>
					</div>
					<div
						v-else
						class="px-4 py-6 text-sm font-bold text-gray-400 dark:text-gray-100 tracking-wider text-center"
					>
						No address found.
					</div>
				</div>
			</dl>
			<dl class="flex flex-wrap">
				<div class="flex-auto pl-6 pt-6">
					<dt
						class="text-md font-semibold leading-6 text-gray-900 dark:text-gray-100 tracking-wide"
					>
						Staff Contact Information
					</dt>
				</div>
				<div class="flex-none self-end px-6 pt-4">
					<button
						class="rounded-md bg-green-50 dark:bg-gray-400 px-2 py-1 text-xs font-medium text-green-600 dark:text-gray-50 ring-1 ring-inset ring-green-600/20 dark:ring-gray-500"
						@click="toggleContactModal()"
					>
						Add Contact
					</button>
				</div>

				<div class="-mx-4 mt-8 flow-root sm:mx-0 w-full px-4">
					<table v-if="contacts" class="min-w-full">
						<colgroup>
							<col class="w-full" />
							<col class="sm:w-1/6" />
						</colgroup>
						<thead
							class="border-b border-gray-300 dark:border-gray-200/30 text-gray-900"
						>
							<tr>
								<th
									scope="col"
									class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 dark:text-gray-100 sm:pl-0"
								>
									Contact type
								</th>
								<th
									scope="col"
									class="hidden px-3 py-3.5 text-right text-sm font-semibold text-gray-900 dark:text-gray-100 sm:table-cell"
								>
									Details
								</th>
								<th></th>
							</tr>
						</thead>
						<tbody>
							<tr
								v-for="contact in contacts"
								:key="contact.id"
								class="border-b border-gray-200 dark:border-gray-200/30"
							>
								<td class="max-w-0 py-5 pl-4 pr-3 text-sm sm:pl-0">
									<div class="font-medium text-gray-900 dark:text-gray-100">
										{{ contact.contact_type_dis }}
									</div>
								</td>
								<td
									class="hidden px-3 py-5 text-right text-sm text-gray-500 dark:text-gray-100 sm:table-cell"
								>
									{{ contact.contact }}
								</td>
								<td class="flex justify-end">
									<SubMenu
										v-if="
											permissions?.includes('update staff') ||
											permissions?.includes('delete staff')
										"
										:can-edit="permissions?.includes('update staff')"
										:can-delete="permissions?.includes('delete staff')"
										:items="['Edit', 'Delete']"
										@item-clicked="(action) => subMenuClicked(action, contact)"
									/>
								</td>
							</tr>
						</tbody>
					</table>
					<div
						v-else
						class="px-4 py-6 text-sm font-bold text-gray-400 dark:text-gray-100 tracking-wider text-center"
					>
						No contacts found.
					</div>
				</div>
			</dl>
		</div>
		<Modal :show="openAddressModal" @close="toggleAddressModal()">
			<AddAddress :person="person" @form-submitted="toggleAddressModal()" />
		</Modal>
		<Modal :show="openContactModal" @close="toggleContactModal()">
			<AddContact :person="person" @form-submitted="toggleContactModal()" />
		</Modal>

		<Modal :show="openEditContactModal" @close="toggleEditContactModal()">
			<EditContact
				:contact="contactModel"
				:person="person"
				@form-submitted="toggleEditContactModal()"
			/>
		</Modal>
	</main>
</template>
