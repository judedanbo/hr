<script setup>
import ChangeStatus from "./partials/ChangeStatus.vue";
import Modal from "@/Components/NewModal.vue";
import { ref } from "vue";
import { useToggle } from "@vueuse/core";
import SubMenu from "@/Components/SubMenu.vue";

const emit = defineEmits(["closeForm", "editHistory", "deleteHistory"]);

let props = defineProps({
	statuses: Array,
	staff: Number,
	institution: Number,
});

let openStatusModal = ref(false);
const toggleStatusModal = useToggle(openStatusModal);

const subMenuClicked = (action, model) => {
	if (action == "Edit") {
		emit("editHistory", model);
	}
	if (action == "Delete") {
		emit("deleteHistory", model);
	}
};

let openEditStaffHistoryModal = ref(false);
const toggleEditStaffHistoryModal = useToggle(openEditStaffHistoryModal);

let openDeleteStaffHistoryModal = ref(false);
const toggleDeleteStaffHistoryModal = useToggle(openDeleteStaffHistoryModal);

const deleteStaffHistory = () => {
	Inertia.delete(
		route("staff-history.delete", {
			staff: props.staff.id,
			staffHistory: staffHistory.value.id,
		}),
		{
			preserveScroll: true,
			onSuccess: () => {
				staffHistory.value = null;
				toggleDeleteStaffHistoryModal();
			},
		},
	);
};
</script>
<template>
	<!-- Transfer History -->
	<main>
		<h2 class="sr-only">Status History</h2>
		<div
			class="rounded-lg bg-gray-50 dark:bg-gray-500 shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-600/80"
		>
			<dl class="flex flex-wrap">
				<div class="flex-auto pl-6 pt-6">
					<dt
						class="text-md tracking-wide font-semibold leading-6 text-gray-900 dark:text-gray-50"
					>
						Status History
					</dt>
				</div>
				<div class="flex-none self-end px-6 pt-4">
					<button
						@click="toggleStatusModal()"
						class="rounded-md bg-green-50 dark:bg-gray-400 px-2 py-1 text-xs font-medium text-green-600 dark:text-gray-50 ring-1 ring-inset ring-green-600/20 dark:ring-gray-500"
					>
						{{ "Change" }}
					</button>
				</div>

				<div class="-mx-4 flow-root sm:mx-0 w-full p-4 overflow-y-auto">
					<table v-if="statuses.length > 0" class="min-w-full">
						<colgroup></colgroup>
						<thead
							class="border-b border-gray-300 text-gray-900 dark:border-gray-200/30 dark:text-gray-50"
						>
							<tr>
								<th
									scope="col"
									class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 dark:text-gray-50 sm:pl-0"
								>
									Status
								</th>
								<th
									scope="col"
									class="hidden px-3 py-3.5 text-right text-sm font-semibold text-gray-900 dark:text-gray-50 sm:table-cell"
								>
									Start
								</th>
								<th
									scope="col"
									class="hidden px-3 py-3.5 text-right text-sm font-semibold text-gray-900 dark:text-gray-50 sm:table-cell"
								>
									End
								</th>
								<th><div class="sr-only">Actions</div></th>
							</tr>
						</thead>
						<tbody>
							<tr
								v-for="status in statuses"
								:key="status.id"
								class="border-b border-gray-200 dark:border-gray-400/30"
							>
								<td class="max-w-0 py-2 pl-1 pr-3 text-sm sm:pl-0">
									<div class="font-medium text-gray-900 dark:text-gray-50">
										{{ status.status }}
									</div>
								</td>
								<td
									class="hidden px-1 py-5 text-right text-sm text-gray-500 dark:text-gray-100 sm:table-cell"
								>
									{{ status.start_date }}
								</td>
								<td
									class="hidden px-1 py-5 text-right text-sm text-gray-500 dark:text-gray-100 sm:table-cell"
								>
									{{ status.end_date }}
								</td>
								<td>
									<SubMenu
										@itemClicked="(action) => subMenuClicked(action, status)"
										:items="['Edit', 'Delete']"
									/>
								</td>
							</tr>
						</tbody>
					</table>
					<div
						v-else
						class="px-4 py-6 text-sm font-bold text-gray-400 dark:text-gray-100 tracking-wider text-center"
					>
						No status found.
					</div>
				</div>
			</dl>
		</div>
		<Modal @close="toggleStatusModal()" :show="openStatusModal">
			<ChangeStatus
				@formSubmitted="toggleStatusModal()"
				:staff="staff"
				:institution="institution"
				:statuses="statuses"
			/>
		</Modal>
		<!-- Edit staff History Modal -->
		<Modal
			@close="toggleEditStaffHistoryModal()"
			:show="openEditStaffHistoryModal"
		>
			<EditStaffHistory
				@formSubmitted="toggleEditStaffHistoryModal()"
				:staff="staff"
				:institution="institution"
				:staffHistory="staffHistory"
			/>
		</Modal>

		<!-- Delete staff History Modal -->
		<Modal
			@close="toggleDeleteStaffHistoryModal()"
			:show="openDeleteStaffHistoryModal"
		>
			<DeleteStaffHistory
				@close="toggleDeleteStaffHistoryModal()"
				@deleteConfirmed="deleteStaffHistory()"
			/>
		</Modal>
	</main>
</template>
