<script setup>
import { format, differenceInYears } from "date-fns";
import { Link } from "@inertiajs/inertia-vue3";
import { ref, watch } from "vue";
import { useToggle } from "@vueuse/core";
import Modal from "@/Components/Modal.vue";
import Promote from "./partials/Promote.vue";
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

watch(
  () => props.showPromotionForm,
  (value) => {
    if (value) {
      openPromoteModal.value = true;
    }
  }
);

const formattedDob = (dob) => {
  if (!dob) return "";
  return new Date(dob).toLocaleDateString("en-GB", {
    day: "numeric",
    month: "short",
    year: "numeric",
  });
};

let getAge = (dateString) => {
  const date = new Date(dateString);
  return differenceInYears(new Date(), date);
};
</script>
<template>
  <!-- Promotion History -->
  <main>
    <h2 class="sr-only">Promotion History</h2>
    <div
      class="rounded-lg bg-gray-50 dark:bg-gray-500 shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-300/80"
    >
      <dl class="flex flex-wrap">
        <div class="flex-auto pl-6 pt-6">
          <dt
            class="text-sm font-semibold leading-6 text-gray-900 dark:text-gray-100"
          >
            Promotion History
          </dt>
        </div>
        <div class="flex-none self-end px-6 pt-4">
          <button
            @click="togglePromoteModal()"
            class="rounded-md bg-green-50 dark:bg-gray-400 px-2 py-1 text-xs font-medium text-green-600 dark:text-gray-50 ring-1 ring-inset ring-green-600/20 dark:ring-gray-200"
          >
            {{ promotions.length > 0 ? "Promote" : "Assign rank" }}
          </button>
        </div>
        <PromotionList :promotions="promotions" />
      </dl>
    </div>
    <Modal @close="togglePromoteModal()" :show="openPromoteModal">
      <Promote
        @formSubmitted="togglePromoteModal()"
        :staff="staff"
        :institution="institution"
      />
    </Modal>
  </main>
</template>
