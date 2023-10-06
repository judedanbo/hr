<script setup>
import { Inertia } from "@inertiajs/inertia";
import { ref, watch } from "vue";
import { useToggle } from "@vueuse/core";
import Modal from "@/Components/Modal.vue";
import Promote from "./partials/Promote.vue";
import Edit from "./partials/Edit.vue";
import Delete from "./partials/Delete.vue";
import PromotionList from "./partials/PromotionList.vue";

const emit = defineEmits(["closeForm"]);

let props = defineProps({
  promotions: Array,
  staff: Number,
  institution: Number,
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
const editPromotion =  (model) => {
  editModel.value = model;
  toggleEditPromotionModal();
};

const openDeletePromotionModal = ref(false);
const toggleDeletePromotionModal = useToggle(openDeletePromotionModal);

const deleteModel = ref(null);
const confirmDeletePromotion = (model) => {
  deleteModel.value = model;
  toggleDeletePromotionModal()
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
    }
  );
};

watch(
  () => props.showPromotionForm,
  (value) => {
    if (value) {
      openPromoteModal.value = true;
    }
  }
);


</script>
<template>
  <!-- Promotion History -->
  <main>
    <h2 class="sr-only">Promotion History</h2>
    <div
      class="rounded-lg bg-gray-50 dark:bg-gray-500 shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-600/80"
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
            @click="togglePromoteModal()"
            class="rounded-md bg-green-50 dark:bg-gray-400 px-2 py-1 text-xs font-medium text-green-600 dark:text-gray-50 ring-1 ring-inset ring-green-600/20 dark:ring-gray-500"
          >
            {{ promotions.length > 0 ? "Promote" : "Assign rank" }}
          </button>
        </div>
        <PromotionList
          @editPromotion="(model) => editPromotion(model)"
          @deletePromotion="(model) => confirmDeletePromotion(model)"
          :promotions="promotions"
        />
      </dl>
    </div>
    <Modal @close="togglePromoteModal()" :show="openPromoteModal">
      <Promote
        @formSubmitted="togglePromoteModal()"
        :staff="staff"
        :institution="institution"
      />
    </Modal>
    <Modal @close="toggleEditPromotionModal()" :show="openEditPromoteModal">
      <Edit
        @formSubmitted="toggleEditPromotionModal()"
        :model="editModel"
        :staff="staff"
        :institution="institution"
      />
    </Modal>
   
    <Delete @deleteConfirmed="deletePromotion(deleteModel.staff_id, deleteModel.rank_id)" @close="toggleDeletePromotionModal()" :open="openDeletePromotionModal" :model="deleteModel" />
  </main>
</template>
