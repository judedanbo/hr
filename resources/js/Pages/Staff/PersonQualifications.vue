<script setup>
import SubMenu from "@/Components/SubMenu.vue";
import { router } from "@inertiajs/vue3";
import AddQualification from "@/Pages/Qualification/Add.vue";
import EditQualification from "@/Pages/Qualification/Edit.vue";
import DeleteQualification from "@/Pages/Qualification/Delete.vue";
import AttachDocument from "@/Pages/Qualification/AttachDocument.vue";
import { usePage } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import { useToggle } from "@vueuse/core";
import NewModal from "@/Components/NewModal.vue";
import QualificationList from "../Qualification/QualificationList.vue";

const page = usePage();
const permissions = computed(() => page.props?.auth.permissions);

// Add Qualification Modal
const openQualificationModal = ref(false);
const toggleQualificationModal = useToggle(openQualificationModal);

// Edit Qualification Modal
const openEditModal = ref(false);
const toggleEditModal = useToggle(openEditModal);

// Delete Qualification Modal
const openDeleteModal = ref(false);
const toggleDeleteModal = useToggle(openDeleteModal);

// Attach Document Modal
const openAttachModal = ref(false);
const toggleAttachModal = useToggle(openAttachModal);

defineProps({
	qualifications: {
		type: Array,
		default: () => [],
	},
	person: {
		type: Object,
		required: true,
	},
});

const qualificationModel = ref(null);

const editQualification = (model) => {
	qualificationModel.value = model;
	toggleEditModal();
};

const confirmDelete = (model) => {
	qualificationModel.value = model;
	toggleDeleteModal();
};

const deleteQualification = () => {
	router.delete(
		route("qualification.delete", {
			qualification: qualificationModel.value.id,
		}),
		{
			preserveScroll: true,
			onSuccess: () => {
				qualificationModel.value = null;
				toggleDeleteModal();
			},
		},
	);
};

const approveQualification = (qualification) => {
	router.patch(
		route("qualification.approve", { qualification: qualification.id }),
		{},
		{
			preserveScroll: true,
		},
	);
};

const attachDocument = (model) => {
	qualificationModel.value = model;
	toggleAttachModal();
};
</script>
<template>
	<main>
		<h2 class="sr-only">staff's Qualifications</h2>
		<div
			class="rounded-lg bg-gray-50 dark:bg-gray-500 shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-600/80"
		>
			<dl class="flex flex-wrap">
				<div class="flex-auto pl-6 pt-6">
					<dt
						class="text-xl font-semibold leading-6 text-gray-900 dark:text-gray-50"
					>
						Qualifications
					</dt>
				</div>
				<div class="flex-none self-end px-6 pt-4 flex gap-2">
					<a
						v-if="permissions?.includes('qualifications.reports.export')"
						:href="route('qualifications.reports.staff.profile.pdf', person.id)"
						class="rounded-md bg-indigo-50 dark:bg-gray-400 px-2 py-1 text-xs font-medium text-indigo-600 dark:text-gray-50 ring-1 ring-inset ring-indigo-600/20 dark:ring-gray-500"
					>
						Download Profile PDF
					</a>
					<button
						v-if="
							permissions?.includes('update staff') ||
							permissions?.includes('create staff qualification')
						"
						class="rounded-md bg-green-50 dark:bg-gray-400 px-2 py-1 text-xs font-medium text-green-600 dark:text-gray-50 ring-1 ring-inset ring-green-600/20 dark:ring-gray-500"
						@click="toggleQualificationModal()"
					>
						{{ "Add Qualification" }}
					</button>
				</div>
				<QualificationList
					:qualifications="qualifications"
					:can-edit="permissions?.includes('edit staff qualification')"
					:can-delete="permissions?.includes('delete staff qualification')"
					:can-approve="permissions?.includes('approve staff qualification')"
					:can-attach="permissions?.includes('edit staff qualification')"
					:can-add-staff-qualification="permissions?.includes('view staff')"
					@edit-qualification="(model) => editQualification(model)"
					@delete-qualification="(model) => confirmDelete(model)"
					@approve-qualification="(model) => approveQualification(model)"
					@attach-document="(model) => attachDocument(model)"
				/>
			</dl>
		</div>

		<!-- Add Qualification Modal -->
		<NewModal
			:show="openQualificationModal"
			@close="toggleQualificationModal()"
		>
			<AddQualification
				:person="person.id"
				:qualificationLevels="page.props.qualificationLevels"
				@form-submitted="toggleQualificationModal()"
			/>
		</NewModal>

		<!-- Edit Qualification Modal -->
		<NewModal :show="openEditModal" @close="toggleEditModal()">
			<EditQualification
				:person="person.id"
				:qualification="qualificationModel"
				@form-submitted="toggleEditModal()"
			/>
		</NewModal>

		<!-- Delete Qualification Modal -->
		<NewModal :show="openDeleteModal" @close="toggleDeleteModal()">
			<DeleteQualification
				:person="person.name"
				@close="toggleDeleteModal()"
				@delete-confirmed="deleteQualification()"
			/>
		</NewModal>

		<!-- Attach Document Modal -->
		<NewModal :show="openAttachModal" @close="toggleAttachModal()">
			<AttachDocument
				:qualification="qualificationModel"
				@form-submitted="
					() => {
						toggleAttachModal();
						router.reload();
					}
				"
				@close="toggleAttachModal()"
			/>
		</NewModal>
	</main>
</template>
