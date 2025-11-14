<script setup>
import Modal from "@/Components/NewModal.vue";
import { ref, computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import { useToggle } from "@vueuse/core";
import AddIdentity from "../Person/partials/AddIdentity.vue";
import EditIdentity from "../Person/partials/EditIdentity.vue";
import SubMenu from "@/Components/SubMenu.vue";
import DeleteIdentity from "./DeleteIdentity.vue";
import { router } from "@inertiajs/vue3";

const props = defineProps({
	identities: { type: Array, default: () => [] },
	person: { type: Number, required: true },
});

const page = usePage();
const permissions = computed(() => page.props?.auth.permissions);

const emit = defineEmits(["editContact", "deleteIdentity"]);
const identityModel = ref(null);

const openDeleteModal = ref(false);
const toggleDeleteModal = useToggle(openDeleteModal);
const subMenuClicked = (action, model) => {
	identityModel.value = model;
	if (action == "Edit") {
		toggleEditIdentityModal();
		// emit("editContact", model);
	}
	if (action == "Delete") {
		toggleDeleteModal();
		// emit("deleteIdentity", model);
	}
};

let openIdentityModal = ref(false);
let toggleIdentityModal = useToggle(openIdentityModal);

let openEditIdentityModal = ref(false);
let toggleEditIdentityModal = useToggle(openEditIdentityModal);

const deleteModalIdentity = () => {
	router.delete(
		route("person.identity.delete", {
			person: props.person,
			identity: identityModel.value.id,
		}),
		{
			preserveScroll: true,
			onSuccess: () => {
				identityModel.value = null;
				toggleDeleteModal();
			},
		},
	);
};
</script>
<template>
	<!-- contact History -->
	<!-- {{ contacts }} -->
	<main class="w-full">
		<h2 class="sr-only">Staff Identifications</h2>
		<div
			class="md:rounded-lg bg-gray-50 dark:bg-gray-500 shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-500/80 px-6 pt-6"
		>
			<dl class="flex items-center justify-between">
				<div class="flex-auto">
					<dt
						class="text-md tracking-wide font-semibold leading-6 text-gray-900 dark:text-gray-100"
					>
						Staff Identifications
					</dt>
				</div>
				<button
					v-if="
						permissions?.includes('update staff') ||
						permissions?.includes('delete staff')
					"
					class="rounded-md bg-green-50 dark:bg-gray-400 px-2 py-1 text-xs font-medium text-green-600 dark:text-gray-50 ring-1 ring-inset ring-green-600/20 dark:ring-gray-500"
					@click="toggleIdentityModal()"
				>
					Add Identification
				</button>
			</dl>
			<dl>
				<div class="mt-2 flow-root sm:mx-0 w-full">
					<table v-if="identities" class="min-w-full">
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
									Identifications
								</th>

								<th></th>
							</tr>
						</thead>
						<tbody>
							<tr v-for="identity in identities" :key="identity.id" class="">
								<td class="max-w-0 text-sm py-2">
									<div
										class="font-medium text-gray-800 dark:text-gray-300 text-xs"
									>
										{{ identity.id_type_display }}
									</div>
									<div class="font-medium text-gray-900 dark:text-gray-50">
										{{ identity.id_number }}
									</div>
								</td>

								<td class="flex justify-center">
									<SubMenu
										v-if="
											permissions?.includes('update staff') ||
											permissions?.includes('delete staff')
										"
										:can-edit="permissions?.includes('update staff')"
										:can-delete="permissions?.includes('delete staff')"
										:items="['Edit', 'Delete']"
										@item-clicked="(action) => subMenuClicked(action, identity)"
									/>
								</td>
							</tr>
						</tbody>
					</table>
					<div
						v-else
						class="px-4 py-6 text-sm font-bold text-gray-400 dark:text-gray-100 tracking-wider text-center"
					>
						No Identifications found.
					</div>
				</div>
			</dl>
		</div>
		<Modal :show="openIdentityModal" @close="toggleIdentityModal()">
			<AddIdentity :person="person" @form-submitted="toggleIdentityModal()" />
		</Modal>

		<Modal :show="openEditIdentityModal" @close="toggleEditIdentityModal()">
			<EditIdentity
				:identity="identityModel"
				:person="person"
				@form-submitted="toggleEditIdentityModal()"
			/>
		</Modal>
		<Modal :show="openDeleteModal" @close="toggleDeleteModal()">
			<DeleteIdentity @deleteConfirmed="deleteModalIdentity()" />
		</Modal>
	</main>
</template>
