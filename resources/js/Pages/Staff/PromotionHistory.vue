<script setup>
import { Inertia } from "@inertiajs/inertia";
import { ref, watch } from "vue";
import { useToggle } from "@vueuse/core";
import Modal from "@/Components/NewModal.vue";
import PromoteStaff from "./partials/PromoteStaff.vue";
import Edit from "./partials/Edit.vue";
import Delete from "./partials/Delete.vue";
import PromotionList from "./partials/PromotionList.vue";

const emit = defineEmits(["closeForm"]);

let props = defineProps({
	promotions: {
		type: Array,
		required: true,
	},
	staff: {
		type: Number,
		required: true,
	},
	institution: {
		type: Number,
		required: true,
	},
	showPromotionForm: {
		type: Boolean,
		default: false,
	},
});

let openPromoteModal = ref(props.showPromotionForm.value);
let togglePromoteModal = () => {
	openPromoteModal.value = false;
	emit("closeForm");
};

const openEditPromoteModal = ref(false);
const toggleEditPromotionModal = useToggle(openEditPromoteModal);
const editModel = ref(null);
const editPromotion = (model) => {
	editModel.value = model;
	toggleEditPromotionModal();
};

const openDeletePromotionModal = ref(false);
const toggleDeletePromotionModal = useToggle(openDeletePromotionModal);

const deleteModel = ref(null);
const confirmDeletePromotion = (model) => {
	deleteModel.value = model;
	toggleDeletePromotionModal();
	// deletePromotion(model.staff_id, model.rank_id);
};

const deletePromotion = (staff_id, rank_id) => {
	Inertia.delete(
		route("staff.promote.delete", { staff: staff_id, job: rank_id }),
		{
			preserveScroll: true,
			onSuccess: () => {
				toggleDeletePromotionModal();
			},
		},
	);
};

watch(
	() => props.showPromotionForm,
	(value) => {
		if (value) {
			openPromoteModal.value = true;
		}
	},
);
</script>
<template>
	<!-- Promotion History -->
	<main>
		<h2 class="sr-only">Promotion History</h2>
		<div
			class="rounded-lg bg-gray-50 dark:bg-gray-500 shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-600/80 max-h-80"
		>
			<dl class="flex flex-wrap">
				<div class="flex-auto pl-6 pt-6">
					<dt
						class="text-md tracking-wide font-semibold leading-6 text-gray-900 dark:text-gray-100"
					>
						Promotion History
					</dt>
				</div>
				<div class="flex-none self-end px-6 pt-4">
					<button
						v-if="$page.props.permissions.includes('update staff')"
						class="rounded-md bg-green-50 dark:bg-gray-400 px-2 py-1 text-xs font-medium text-green-600 dark:text-gray-50 ring-1 ring-inset ring-green-600/20 dark:ring-gray-500"
						@click="togglePromoteModal()"
					>
						{{ promotions.length > 0 ? "Promote" : "Assign rank" }}
					</button>
				</div>
				<PromotionList
					class="w-full max-h-64 overflow-y-scroll"
					:promotions="promotions"
					@edit-promotion="(model) => editPromotion(model)"
					@delete-promotion="(model) => confirmDeletePromotion(model)"
				/>
			</dl>
		</div>
		<Modal :show="openPromoteModal" @close="togglePromoteModal()">
			<PromoteStaff
				:staff="staff"
				:institution="institution"
				@form-submitted="togglePromoteModal()"
			/>
		</Modal>
		<Modal :show="openEditPromoteModal" @close="toggleEditPromotionModal()">
			<Edit
				:model="editModel"
				:staff="staff"
				:institution="institution"
				@form-submitted="toggleEditPromotionModal()"
			/>
		</Modal>

		<Delete
			:open="openDeletePromotionModal"
			:model="deleteModel"
			@delete-confirmed="
				deletePromotion(deleteModel.staff_id, deleteModel.rank_id)
			"
			@close="toggleDeletePromotionModal()"
		/>
	</main>
</template>
