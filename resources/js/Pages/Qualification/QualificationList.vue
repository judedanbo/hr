<script setup>
import { PaperClipIcon } from "@heroicons/vue/20/solid";
import SubMenu from "@/Components/SubMenu.vue";
import ToolTip from "@/Components/ToolTip.vue";
import Modal from "@/Components/NewModal.vue";
import { useToggle } from "@vueuse/core";
import { ref } from "vue";
import { Inertia } from "@inertiajs/inertia";
import DocumentPreview from "./partials/DocumentPreview.vue";

const emit = defineEmits(["editQualification", "deleteQualification"]);
defineProps({
	qualifications: {
		type: Array,
		default: () => [],
	},
});

const showPreviewDocumentModal = ref(false);
const togglePreviewDocumentModal = useToggle(showPreviewDocumentModal);

const documentUrl = ref("");
const documentFileType = ref("");
const previewDocument = (document) => {
	togglePreviewDocumentModal();
	documentUrl.value = document.file_name;
	documentFileType.value = document.file_type;
};

const deleteDocument = (qualification) => {
	Inertia.delete(
		route("qualification-document.delete", {
			qualification: qualification.id,
		}),
		{
			preserveScroll: true,
			onSuccess: () => {
				// togglePreviewDocumentModal();
			},
			onError: (errors) => {
				// const errorNode = getNode("documentUpload");
				// errorNode.setErrors(errors);
				// errorNode = { errors: "there are errors" }; // TODO fix display server side image errors
			},
		},
	);
};
const subMenuClicked = (action, model) => {
	if (action == "Edit") {
		emit("editQualification", model);
	}
	if (action == "Delete") {
		emit("deleteQualification", model);
	}
};
</script>
<template>
	<div class="-mx-4 flow-root sm:mx-0 w-full px-4">
		<table v-if="qualifications.length > 0" class="min-w-full">
			<colgroup></colgroup>
			<thead
				class="border-b border-gray-300 dark:border-gray-200/50 text-gray-900 dark:text-gray-50"
			>
				<tr>
					<th
						scope="col"
						class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 dark:text-gray-50 sm:pl-0"
					></th>
					<th
						scope="col"
						class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 dark:text-gray-50 sm:pl-0"
					>
						Institution
					</th>
					<th
						scope="col"
						class="hidden px-3 py-3.5 text-sm font-semibold text-gray-900 dark:text-gray-50 sm:table-cell"
					>
						Level
					</th>
					<th
						scope="col"
						class="hidden px-3 py-3.5 text-sm font-semibold text-gray-900 dark:text-gray-50 sm:table-cell"
					>
						Course
					</th>
					<th
						scope="col"
						class="hidden px-3 py-3.5 text-sm font-semibold text-gray-900 dark:text-gray-50 sm:table-cell"
					>
						Qualification
					</th>
					<th
						scope="col"
						class="hidden px-3 py-3.5 text-sm text-center font-semibold text-gray-900 dark:text-gray-50 sm:table-cell"
					>
						Year
					</th>
					<!-- <th
						scope="col"
						class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 dark:text-gray-50 sm:pl-0"
					>
						Documents
					</th> -->
					<th><div class="sr-only">Actions</div></th>
				</tr>
			</thead>
			<tbody>
				<tr
					v-for="qualification in qualifications"
					:key="qualification.id"
					class="border-b border-gray-200 dark:border-gray-400/30"
				>
					<td class="max-w-0 py-2 pl-1 pr-3 text-sm sm:pl-0">
						<div class="font-medium text-gray-900 dark:text-gray-50">
							{{ qualification.person }}
						</div>
						{{ qualification.staff_number }}
					</td>
					<td class="max-w-0 py-2 pl-1 pr-3 text-sm sm:pl-0">
						<div class="font-medium text-gray-900 dark:text-gray-50">
							{{ qualification.institution }}
						</div>
					</td>
					<td
						class="hidden px-1 py-5 text-sm text-gray-500 dark:text-gray-100 sm:table-cell"
					>
						{{ qualification.level }}
					</td>
					<td
						class="hidden px-1 py-5 text-sm text-gray-500 dark:text-gray-100 sm:table-cell"
					>
						{{ qualification.course }}
					</td>

					<td
						class="hidden px-1 py-5 text-sm text-gray-500 dark:text-gray-100 sm:table-cell"
					>
						{{ qualification.qualification }}
						<div class="font-medium text-xs text-gray-900 dark:text-gray-50">
							{{ qualification.qualification_number }}
						</div>
					</td>
					<td
						class="hidden px-1 py-5 text-sm text-gray-500 dark:text-gray-100 sm:table-cell text-right"
					>
						{{ qualification.year }}
					</td>
					<!-- <td class="w-8"> -->
					<!-- {{ qualification.documents[0].document_title }} -->
					<!-- <ToolTip
							v-if="qualification.documents?.length > 0"
							:tooltip="qualification.documents[0].document_title"
							:options="true"
							@preview="() => previewDocument(qualification.documents[0])"
							@delete="() => deleteDocument(qualification)"
						>
							<PaperClipIcon
								v-if="qualification.documents?.length > 0"
								class="mx-auto w-8 h-8 text-gray-400 dark:text-gray-50 hover:text-green-700 dark:hover:text-white cursor-pointer hover:bg-green-100 dark:hover:bg-gray-800 rounded-full p-1"
							/>
						</ToolTip> -->
					<!-- </td> -->
					<td class="flex justify-end">
						<SubMenu
							v-if="
								$page.props.permissions.includes('create staff qualification')
							"
							:items="['Edit', 'Delete']"
							@item-clicked="(action) => subMenuClicked(action, qualification)"
						/>
					</td>
				</tr>
			</tbody>
		</table>
		<div
			v-else
			class="px-4 py-6 text-sm font-bold text-gray-400 dark:text-gray-100 tracking-wider text-center"
		>
			No qualifications found.
		</div>
	</div>
	<Modal :show="showPreviewDocumentModal" @close="togglePreviewDocumentModal">
		<DocumentPreview :url="documentUrl" :type="documentFileType" />
	</Modal>
</template>
