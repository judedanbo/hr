<script setup>
import { ref, onMounted } from "vue";
import { TrashIcon, PlusIcon } from "@heroicons/vue/24/outline";

const contact_types = ref([]);
const contacts = ref([]);

onMounted(async () => {
	const { data } = await axios.get(route("contact-type.index"));
	contact_types.value = data;
});

const addContact = () => {
	contacts.value.push({
		contact_type: "",
		contact: "",
	});
};

const removeContact = (index) => {
	contacts.value.splice(index, 1);
};
</script>

<template>
	<div class="space-y-4">
		<div class="flex items-center justify-between">
			<h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
				Dependent Contacts
			</h3>
			<button
				type="button"
				class="inline-flex items-center gap-1 rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600"
				@click="addContact"
			>
				<PlusIcon class="h-4 w-4" />
				Add Contact
			</button>
		</div>

		<p
			v-if="contacts.length === 0"
			class="text-sm text-gray-500 dark:text-gray-400"
		>
			No contacts added. Click "Add Contact" to add contact information for this
			dependent.
		</p>

		<FormKit type="list" name="contacts">
			<FormKit
				v-for="(contact, index) in contacts"
				:key="index"
				:index="index"
				type="group"
			>
				<div
					class="flex items-start gap-4 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-600 dark:bg-gray-800"
				>
					<div class="flex-1 grid grid-cols-1 gap-4 sm:grid-cols-2">
						<FormKit
							v-model="contacts[index].contact_type"
							type="select"
							name="contact_type"
							label="Contact Type"
							placeholder="Select type"
							validation="required"
							:options="contact_types"
						/>
						<FormKit
							v-model="contacts[index].contact"
							type="text"
							name="contact"
							label="Contact"
							placeholder="Enter contact"
							validation="required|length:2,100"
						/>
					</div>
					<button
						type="button"
						class="mt-6 rounded-md bg-red-50 p-2 text-red-600 hover:bg-red-100 dark:bg-red-900/20 dark:text-red-400 dark:hover:bg-red-900/40"
						@click="removeContact(index)"
					>
						<TrashIcon class="h-5 w-5" />
					</button>
				</div>
			</FormKit>
		</FormKit>
	</div>
</template>
