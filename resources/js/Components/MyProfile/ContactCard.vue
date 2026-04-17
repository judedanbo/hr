<script setup>
import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";
import { useToggle } from "@vueuse/core";
import NewModal from "@/Components/NewModal.vue";
import AddContact from "@/Pages/Person/partials/AddContact.vue";
import EditContact from "@/Pages/Person/partials/EditContact.vue";

const CONTACT_TYPE_PHONE = 2;
const CONTACT_TYPE_EMAIL = 1;
const PROTECTED_EMAIL_DOMAIN = "audit.gov.gh";

const props = defineProps({
	personId: { type: Number, required: true },
	contacts: { type: Array, default: () => null },
});

const openAdd = ref(false);
const openEdit = ref(false);
const openDelete = ref(false);
const toggleAdd = useToggle(openAdd);
const toggleEdit = useToggle(openEdit);
const toggleDelete = useToggle(openDelete);

const current = ref(null);

const activeContacts = computed(() => {
	const active = (props.contacts ?? []).filter((c) => !c.valid_end);
	// Pin protected org emails to the top. Stable sort preserves insertion
	// order for every other contact.
	return [...active].sort((a, b) => {
		const ap = isProtectedOrgEmail(a) ? 0 : 1;
		const bp = isProtectedOrgEmail(b) ? 0 : 1;
		return ap - bp;
	});
});

function isLastActivePhone(c) {
	if (c.contact_type !== CONTACT_TYPE_PHONE) {
		return false;
	}
	if (c.valid_end) {
		return false;
	}
	const otherActivePhones = activeContacts.value.filter(
		(x) =>
			x.id !== c.id && x.contact_type === CONTACT_TYPE_PHONE && !x.valid_end,
	);
	return otherActivePhones.length === 0;
}

function isProtectedOrgEmail(c) {
	if (c.contact_type !== CONTACT_TYPE_EMAIL) {
		return false;
	}
	const email = String(c.contact ?? "").toLowerCase();
	const at = email.lastIndexOf("@");
	if (at === -1) {
		return false;
	}
	return email.slice(at + 1) === PROTECTED_EMAIL_DOMAIN;
}

function startEdit(contact) {
	current.value = contact;
	toggleEdit();
}

function startDelete(contact) {
	current.value = contact;
	toggleDelete();
}

function confirmDelete() {
	router.delete(
		route("person.contact.delete", {
			person: props.personId,
			contact: current.value.id,
		}),
		{
			preserveScroll: true,
			onSuccess: () => {
				current.value = null;
				toggleDelete();
				router.reload({ only: ["contacts"] });
			},
		},
	);
}

function onMutationSuccess() {
	router.reload({ only: ["contacts"] });
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
				@click="toggleAdd()"
			>
				+ Add
			</button>
		</header>

		<ul v-if="activeContacts.length > 0" class="space-y-2">
			<li
				v-for="c in activeContacts"
				:key="c.id"
				class="flex items-center gap-2 text-sm"
			>
				<span
					class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 whitespace-nowrap"
				>
					{{ c.contact_type_dis }}
				</span>
				<span class="flex-1 truncate text-gray-900 dark:text-gray-100">{{
					c.contact
				}}</span>
				<button
					v-if="!isProtectedOrgEmail(c)"
					type="button"
					class="text-[11px] font-semibold text-emerald-700 dark:text-emerald-300 hover:underline"
					@click="startEdit(c)"
				>
					Edit
				</button>
				<template v-if="isLastActivePhone(c) || isProtectedOrgEmail(c)">
					<span class="text-[11px] italic text-gray-500 dark:text-gray-400">{{
						isProtectedOrgEmail(c) ? "Org email" : "Required"
					}}</span>
				</template>
				<button
					v-else
					type="button"
					class="text-[11px] font-semibold text-red-600 dark:text-red-400 hover:underline"
					@click="startDelete(c)"
				>
					Delete
				</button>
			</li>
		</ul>
		<p v-else class="text-sm text-gray-500 dark:text-gray-400 py-2">
			No contacts on file. Click <strong>Add</strong> to add one.
		</p>

		<NewModal :show="openAdd" @close="toggleAdd()">
			<AddContact
				:person="personId"
				@form-submitted="
					() => {
						toggleAdd();
						onMutationSuccess();
					}
				"
			/>
		</NewModal>

		<NewModal :show="openEdit" @close="toggleEdit()">
			<EditContact
				v-if="current"
				:person="personId"
				:contact="current"
				@form-submitted="
					() => {
						toggleEdit();
						onMutationSuccess();
					}
				"
			/>
		</NewModal>

		<NewModal :show="openDelete" @close="toggleDelete()">
			<div class="bg-gray-100 dark:bg-gray-800 px-8 py-8">
				<h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-2">
					Delete contact?
				</h2>
				<p v-if="current" class="text-sm text-gray-600 dark:text-gray-300 mb-5">
					Remove <strong>{{ current.contact }}</strong> ({{
						current.contact_type_dis
					}}) from your profile. This cannot be undone.
				</p>
				<div class="flex justify-end gap-2">
					<button
						type="button"
						class="text-sm text-gray-600 dark:text-gray-400 hover:underline px-3 py-1.5"
						@click="toggleDelete()"
					>
						Cancel
					</button>
					<button
						type="button"
						class="text-sm font-semibold text-white bg-red-600 hover:bg-red-700 rounded-lg px-3 py-1.5"
						@click="confirmDelete"
					>
						Delete
					</button>
				</div>
			</div>
		</NewModal>
	</section>
</template>
