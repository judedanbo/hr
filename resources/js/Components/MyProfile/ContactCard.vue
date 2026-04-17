<script setup>
import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";
import { useToggle } from "@vueuse/core";
import NewModal from "@/Components/NewModal.vue";

// ContactTypeEnum integer values (backed int enum in PHP)
const CONTACT_TYPE_EMAIL = 1;
const CONTACT_TYPE_PHONE = 2;

const props = defineProps({
	personId: { type: Number, required: true },
	contacts: { type: Array, default: () => null },
	address: { type: Object, default: () => null },
});

const openEdit = ref(false);
const toggleEdit = useToggle(openEdit);

const activeContacts = computed(() =>
	(props.contacts ?? []).filter((c) => !c.valid_end),
);

const primaryEmail = computed(
	() =>
		activeContacts.value.find((c) => c.contact_type === CONTACT_TYPE_EMAIL) ??
		null,
);
const primaryPhone = computed(
	() =>
		activeContacts.value.find((c) => c.contact_type === CONTACT_TYPE_PHONE) ??
		null,
);

const addressDisplay = computed(() => {
	if (!props.address) return "—";
	const parts = [
		props.address.address_line_1,
		props.address.city,
		props.address.region,
	].filter(Boolean);
	return parts.length ? parts.join(", ") : "—";
});

// Local editable copies for the modal form
const editableContacts = ref([]);

function openEditModal() {
	editableContacts.value = activeContacts.value.map((c) => ({ ...c }));
	toggleEdit();
}

function saveContact(c) {
	router.patch(
		route("contact.update", { contact: c.id }),
		{
			contact_type: c.contact_type,
			contact: c.contact,
			valid_end: c.valid_end ?? null,
		},
		{
			preserveScroll: true,
			onSuccess: () => {
				toggleEdit();
				router.reload({ only: ["contacts"] });
			},
		},
	);
}
</script>

<template>
	<section
		class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-sm"
	>
		<header class="flex justify-between items-center mb-3">
			<h3 class="text-sm font-bold text-gray-900 dark:text-gray-50">Contact</h3>
			<button
				type="button"
				class="text-[11px] font-semibold text-emerald-700 dark:text-emerald-300 hover:underline"
				@click="openEditModal()"
			>
				Edit
			</button>
		</header>

		<dl class="divide-y divide-gray-100 dark:divide-gray-700 text-sm">
			<div class="flex justify-between py-1.5">
				<dt class="text-gray-500 dark:text-gray-400">Email</dt>
				<dd
					class="font-medium text-gray-900 dark:text-gray-100 truncate max-w-[60%] text-right"
				>
					{{ primaryEmail?.contact ?? "—" }}
				</dd>
			</div>
			<div class="flex justify-between py-1.5">
				<dt class="text-gray-500 dark:text-gray-400">Phone</dt>
				<dd
					class="font-medium text-gray-900 dark:text-gray-100 truncate max-w-[60%] text-right"
				>
					{{ primaryPhone?.contact ?? "—" }}
				</dd>
			</div>
			<div class="flex justify-between py-1.5">
				<dt class="text-gray-500 dark:text-gray-400">Address</dt>
				<dd
					class="font-medium text-gray-900 dark:text-gray-100 truncate max-w-[60%] text-right"
				>
					{{ addressDisplay }}
				</dd>
			</div>
		</dl>

		<NewModal :show="openEdit" @close="toggleEdit()">
			<div class="bg-gray-100 dark:bg-gray-700 px-6 py-6">
				<h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">
					Edit Contacts
				</h2>

				<div
					v-if="editableContacts.length === 0"
					class="text-sm text-gray-500 dark:text-gray-400 py-4 text-center"
				>
					No contacts on record. Contact HR to add contact information.
				</div>

				<ul v-else class="space-y-4">
					<li
						v-for="c in editableContacts"
						:key="c.id"
						class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-600"
					>
						<p
							class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-2"
						>
							{{ c.contact_type_dis }}
						</p>
						<div class="flex gap-2 items-center">
							<input
								v-model="c.contact"
								type="text"
								class="flex-1 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-sm text-gray-900 dark:text-gray-100 px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-emerald-500"
							/>
							<button
								type="button"
								class="text-[11px] font-semibold text-emerald-700 dark:text-emerald-300 hover:underline whitespace-nowrap"
								@click="saveContact(c)"
							>
								Save
							</button>
						</div>
					</li>
				</ul>

				<div class="mt-4 flex justify-end">
					<button
						type="button"
						class="text-sm text-gray-600 dark:text-gray-400 hover:underline"
						@click="toggleEdit()"
					>
						Close
					</button>
				</div>
			</div>
		</NewModal>
	</section>
</template>
