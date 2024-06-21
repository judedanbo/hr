<script setup>
import Create from "./Create.vue";
import EditStaffStatus from "./Edit.vue";
import DeleteStaffStatus from "./Delete.vue";
import Modal from "@/Components/NewModal.vue";
import { ref } from "vue";
import { Inertia } from "@inertiajs/inertia";
import { useToggle } from "@vueuse/core";
import StaffStatusHistory from "./partials/StaffStatusHistory.vue";

const emit = defineEmits(["closeForm", "editHistory", "deleteHistory"]);

let props = defineProps({
	statuses: { type: Array, required: true },
	staff: { type: Object, required: true },
	institution: { type: Number, required: true },
});

let openStatusModal = ref(false);
const toggleStatusModal = useToggle(openStatusModal);

let openEditStaffModal = ref(false);
const toggleEditStaffStatusModal = useToggle(openEditStaffModal);

const staffStatus = ref(null);
const editStaffStatus = (modal) => {
	staffStatus.value = modal;
	toggleEditStaffStatusModal();
};

let openDeleteStaffStatusModal = ref(false);
const toggleDeleteStaffStatusModal = useToggle(openDeleteStaffStatusModal);

const confirmDelete = (model) => {
	staffStatus.value = model;
	toggleDeleteStaffStatusModal();
};
const deleteStaffStatus = () => {
	Inertia.delete(
		route("staff-status.delete", {
			staff: props.staff.id,
			staffStatus: staffStatus.value.id,
		}),
		{
			preserveScroll: true,
			onSuccess: () => {
				staffStatus.value = null;
				toggleDeleteStaffStatusModal();
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
			class="rounded-lg bg-gray-50 dark:bg-gray-500 shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-400/50"
		>
			<dl class="flex flex-wrap">
				<div class="flex-auto pl-6 pt-4">
					<dt
						class="text-md tracking-wide font-semibold leading-6 text-gray-900 dark:text-gray-50"
					>
						Status History
					</dt>
				</div>
				<div class="flex-none self-end px-6 pt-4">
					<button
						v-if="
							$page.props.permissions.includes('update staff') ||
							$page.props.permissions.includes('delete staff')
						"
						class="rounded-md bg-green-50 dark:bg-gray-400 px-2 py-1 text-xs font-medium text-green-600 dark:text-gray-50 ring-1 ring-inset ring-green-600/20 dark:ring-gray-500"
						@click="toggleStatusModal()"
					>
						{{ "Change" }}
					</button>
				</div>
				<StaffStatusHistory
					:statuses="statuses"
					@edit-staff-status="(model) => editStaffStatus(model)"
					@delete-staff-status="(model) => confirmDelete(model)"
				/>
			</dl>
		</div>
		<Modal :show="openStatusModal" @close="toggleStatusModal()">
			<Create
				:staff="staff"
				:institution="institution"
				:statuses="statuses"
				@form-submitted="toggleStatusModal()"
			/>
		</Modal>
		<!-- Edit staff History Modal -->
		<Modal :show="openEditStaffModal" @close="toggleEditStaffStatusModal()">
			<EditStaffStatus
				:institution="institution"
				:staff-status="staffStatus"
				:staff="staff"
				@form-submitted="toggleEditStaffStatusModal()"
			/>
		</Modal>

		<!-- Delete staff History Modal -->
		<Modal
			:show="openDeleteStaffStatusModal"
			@close="toggleDeleteStaffStatusModal()"
		>
			<DeleteStaffStatus
				@close="toggleDeleteStaffStatusModal()"
				@delete-confirmed="deleteStaffStatus()"
			/>
		</Modal>
	</main>
</template>
