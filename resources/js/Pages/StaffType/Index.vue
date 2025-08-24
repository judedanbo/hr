<script setup>
import Create from "./Create.vue";
import Modal from "@/Components/NewModal.vue";
import { ref, computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import { useToggle } from "@vueuse/core";
import SubMenu from "@/Components/SubMenu.vue";
import EditStaffType from "./Edit.vue";
import DeleteStaffType from "./Delete.vue";
import { router } from "@inertiajs/vue3";

const page = usePage();
const permissions = computed(() => page.props?.auth.permissions);

const emit = defineEmits(["closeForm", "editType", "deleteType"]);
let props = defineProps({
	types: { type: Array, required: true },
	staff: { type: Object, required: true },
	institution: { type: Number, required: true },
});

let openStaffTypeModal = ref(false);
const toggleStaffTypeModal = useToggle(openStaffTypeModal);
const staffType = ref(null);
const subMenuClicked = (action, model) => {
	if (action == "Edit") {
		staffType.value = model;
		toggleEditStaffTypeModal();
	}
	if (action == "Delete") {
		staffType.value = model;
		toggleDeleteStaffTypeModal();
	}
};

const openEditStaffTypeModal = ref(false);
const toggleEditStaffTypeModal = useToggle(openEditStaffTypeModal);

const openDeleteStaffTypeModal = ref(false);
const toggleDeleteStaffTypeModal = useToggle(openDeleteStaffTypeModal);

const deleteStaffType = () => {
	router.delete(
		route("staff-type.delete", {
			staff: props.staff.id,
			staffType: staffType.value.id,
		}),
		{
			preserveScroll: true,
			onSuccess: () => {
				staffType.value = null;
				toggleDeleteStaffTypeModal();
			},
		},
	);
};
</script>
<template>
	<!-- Transfer History -->
	<main>
		<h2 class="sr-only">Staff Type</h2>
		<div
			class="rounded-lg bg-gray-50 dark:bg-gray-500 shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-400/50"
		>
			<dl class="flex flex-wrap">
				<div class="flex-auto pl-6 pt-4">
					<dt
						class="text-md tracking-wide font-semibold leading-6 text-gray-900 dark:text-gray-50"
					>
						Staff Type
					</dt>
				</div>
				<div class="flex-none self-end px-6 pt-4">
					<button
						v-if="
							permissions?.includes('update staff') ||
							permissions?.includes('delete staff')
						"
						class="rounded-md bg-green-50 dark:bg-gray-400 px-2 py-1 text-xs font-medium text-green-600 dark:text-gray-50 ring-1 ring-inset ring-green-600/20 dark:ring-gray-500"
						@click="toggleStaffTypeModal()"
					>
						{{ "Change" }}
					</button>
				</div>

				<div class="-mx-4 flow-root sm:mx-0 w-full px-4 overflow-y-auto">
					<table v-if="types.length > 0" class="min-w-full">
						<colgroup></colgroup>
						<thead
							class="border-b border-gray-300 text-gray-900 dark:border-gray-200/30 dark:text-gray-50"
						>
							<tr>
								<th
									scope="col"
									class="py-2 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 dark:text-gray-50 sm:pl-0"
								>
									Type
								</th>
								<th><div class="sr-only">Actions</div></th>
							</tr>
						</thead>
						<tbody>
							<tr
								v-for="type in types"
								:key="type.id"
								class="border-b border-gray-200 dark:border-gray-400/30"
							>
								<td class="max-w-0 py-2 pl-1 pr-3 text-xs sm:pl-0">
									<div
										class="font-medium text-gray-900 dark:text-gray-50 w-3/5"
									>
										{{ type.type_label }}
									</div>
									<div
										class="mt-1 truncate text-gray-500 dark:text-gray-100 text-xs"
									>
										{{ type.start_date_display }}
										{{
											type.end_date_display?.length > 0
												? " - " + type.end_date_display
												: " to date"
										}}
									</div>
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
										@item-clicked="(action) => subMenuClicked(action, type)"
									/>
								</td>
							</tr>
						</tbody>
					</table>
					<div
						v-else
						class="px-4 py-6 text-sm font-bold text-gray-400 dark:text-gray-100 tracking-wider text-center"
					>
						No staff type found.
					</div>
				</div>
			</dl>
		</div>
		<Modal :show="openStaffTypeModal" @close="toggleStaffTypeModal()">
			<Create
				:staff="staff"
				:staff-type="staffType"
				:institution="institution"
				@form-submitted="toggleStaffTypeModal()"
			/>
		</Modal>
		<!-- Edit staff Type Modal -->
		<Modal :show="openEditStaffTypeModal" @close="toggleEditStaffTypeModal()">
			<EditStaffType
				:staff="staff"
				:institution="institution"
				:staff-type="staffType"
				@form-submitted="toggleEditStaffTypeModal()"
			/>
		</Modal>

		<!-- Delete staff type Modal -->
		<Modal
			:show="openDeleteStaffTypeModal"
			@close="toggleDeleteStaffTypeModal()"
		>
			<DeleteStaffType
				@close="toggleDeleteStaffTypeModal()"
				@delete-confirmed="deleteStaffType()"
			/>
		</Modal>
	</main>
</template>
