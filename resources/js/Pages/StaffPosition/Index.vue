<script setup>
import Create from "./Create.vue";
import Modal from "@/Components/NewModal.vue";
import { ref, computed } from "vue";
import { useToggle } from "@vueuse/core";
import SubMenu from "@/Components/SubMenu.vue";
import EditStaffPosition from "./Edit.vue";
import DeleteStaffPosition from "./Delete.vue";
import { router } from "@inertiajs/vue3";
import { usePage } from "@inertiajs/vue3";

const page = usePage();
const permissions = computed(() => page.props?.auth.permissions);

const emit = defineEmits(["closeForm", "editPosition", "deletePosition"]);
let props = defineProps({
	positions: { position: Array, required: true },
	staff: { position: Object, required: true },
	institution: { position: Number, required: true },
});

let openStaffPositionModal = ref(false);
const toggleStaffPositionModal = useToggle(openStaffPositionModal);
const staffPosition = ref(null);
const subMenuClicked = (action, model) => {
	if (action == "Edit") {
		staffPosition.value = model;
		toggleEditStaffPositionModal();
	}
	if (action == "Delete") {
		staffPosition.value = model;
		toggleDeleteStaffPositionModal();
	}
};

const openEditStaffPositionModal = ref(false);
const toggleEditStaffPositionModal = useToggle(openEditStaffPositionModal);

const openDeleteStaffPositionModal = ref(false);
const toggleDeleteStaffPositionModal = useToggle(openDeleteStaffPositionModal);

const deleteStaffPosition = () => {
	router.delete(
		route("staff.position.delete", {
			staff: props.staff.id,
			staffPosition: staffPosition.value.id,
		}),
		{
			preserveScroll: true,
			onSuccess: () => {
				staffPosition.value = null;
				toggleDeleteStaffPositionModal();
			},
		},
	);
};
</script>
<template>
	<!-- Transfer History -->
	<main>
		<h2 class="sr-only">Staff Position</h2>
		<div
			class="rounded-lg bg-gray-50 dark:bg-gray-500 shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-400/50"
		>
			<dl class="flex flex-wrap">
				<div class="flex-auto pl-6 pt-4">
					<dt
						class="text-md tracking-wide font-semibold leading-6 text-gray-900 dark:text-gray-50"
					>
						Staff Position
					</dt>
				</div>
				<div class="flex-none self-end px-6 pt-4">
					<button
						v-if="
							permissions?.includes('update staff') ||
							permissions?.includes('delete staff')
						"
						class="rounded-md bg-green-50 dark:bg-gray-400 px-2 py-1 text-xs font-medium text-green-600 dark:text-gray-50 ring-1 ring-inset ring-green-600/20 dark:ring-gray-500"
						@click="toggleStaffPositionModal()"
					>
						{{ "Change" }}
					</button>
				</div>

				<div class="-mx-4 flow-root sm:mx-0 w-full px-4 overflow-y-auto">
					<table v-if="positions.length > 0" class="min-w-full">
						<colgroup></colgroup>
						<thead
							class="border-b border-gray-300 text-gray-900 dark:border-gray-200/30 dark:text-gray-50"
						>
							<tr>
								<th
									scope="col"
									class="py-2 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 dark:text-gray-50 sm:pl-0"
								>
									Position
								</th>
								<th><div class="sr-only">Actions</div></th>
							</tr>
						</thead>
						<tbody>
							<tr
								v-for="position in positions"
								:key="position.id"
								class="border-b border-gray-200 dark:border-gray-400/30"
							>
								<td class="max-w-0 py-2 pl-1 pr-3 text-xs sm:pl-0">
									<div
										class="font-medium text-gray-900 dark:text-gray-50 w-3/5"
									>
										{{ position.name }}
									</div>
									<div
										class="mt-1 truncate text-gray-500 dark:text-gray-100 text-xs"
									>
										{{ position.start_date_display }}
										{{
											position.end_date_display?.length > 0
												? " - " + position.end_date_display
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
										:items="['Edit', 'Delete']"
										@item-clicked="(action) => subMenuClicked(action, position)"
									/>
								</td>
							</tr>
						</tbody>
					</table>
					<div
						v-else
						class="px-4 py-6 text-sm font-bold text-gray-400 dark:text-gray-100 tracking-wider text-center"
					>
						No staff position.
					</div>
				</div>
			</dl>
		</div>
		<Modal :show="openStaffPositionModal" @close="toggleStaffPositionModal()">
			<Create
				:staff="staff"
				:staff-position="staffPosition"
				:institution="institution"
				@form-submitted="toggleStaffPositionModal()"
			/>
		</Modal>
		<!-- Edit staff Position Modal -->
		<Modal
			:show="openEditStaffPositionModal"
			@close="toggleEditStaffPositionModal()"
		>
			<EditStaffPosition
				:staff="staff"
				:institution="institution"
				:staff-position="staffPosition"
				@form-submitted="toggleEditStaffPositionModal()"
			/>
		</Modal>

		<!-- Delete staff position Modal -->
		<Modal
			:show="openDeleteStaffPositionModal"
			@close="toggleDeleteStaffPositionModal()"
		>
			<DeleteStaffPosition
				@close="toggleDeleteStaffPositionModal()"
				@delete-confirmed="deleteStaffPosition()"
			/>
		</Modal>
	</main>
</template>
