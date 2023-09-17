<script setup>
import { format, differenceInYears } from "date-fns";
import { Link } from "@inertiajs/inertia-vue3";
import Transfer from "./partials/Transfer.vue";
import Modal from "@/Components/Modal.vue";
import { ref, watch } from "vue";
import { useToggle } from "@vueuse/core";

const emit = defineEmits(["closeForm"]);

let props = defineProps({
  transfers: Array,
  staff: Number,
  institution: Number,
  showTransferForm: {
    type: Boolean,
    default: false,
  },
});

let openTransferModal = ref(props.showTransferForm.value);
let toggleTransferModal = () => {
  openTransferModal.value = false;
  emit("closeForm");
};

watch(
  () => props.showTransferForm,
  (value) => {
    if (value) {
      openTransferModal.value = true;
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
  <!-- Transfer History -->
  <main>
    <h2 class="sr-only">Transfer History</h2>
    <div
      class="rounded-lg bg-gray-50 dark:bg-gray-500 shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-300/80"
    >
      <dl class="flex flex-wrap">
        <div class="flex-auto pl-6 pt-6">
          <dt
            class="text-sm font-semibold leading-6 text-gray-900 dark:text-gray-50"
          >
            Transfer History
          </dt>
        </div>
        <div class="flex-none self-end px-6 pt-4">
          <button
            @click="toggleTransferModal()"
            class="rounded-md bg-green-50 dark:bg-gray-400 px-2 py-1 text-xs font-medium text-green-600 dark:text-gray-50 ring-1 ring-inset ring-green-600/20 dark:ring-gray-200"
          >
            {{ transfers.length > 0 ? "Transfer" : "First Posting" }}
          </button>
        </div>

        <div class="-mx-4 mt-8 flow-root sm:mx-0 w-full px-4">
          <table v-if="transfers.length > 0" class="min-w-full">
            <colgroup></colgroup>
            <thead
              class="border-b border-gray-300 text-gray-900 dark:text-gray-50"
            >
              <tr>
                <th
                  scope="col"
                  class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 dark:text-gray-50 sm:pl-0"
                >
                  Post
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
                <!-- <th scope="col" class="py-3.5 pl-3 pr-4 text-right text-sm font-semibold text-gray-900 dark:text-gray-50 sm:pr-0">Duration</th> -->
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="transfer in transfers"
                :key="transfer.id"
                class="border-b border-gray-200"
              >
                <td class="max-w-0 py-2 pl-1 pr-3 text-sm sm:pl-0">
                  <div class="font-medium text-gray-900 dark:text-gray-50">
                    {{ transfer.name }}
                  </div>
                  <div class="mt-1 truncate text-gray-500 dark:text-gray-100">
                    {{ transfer.remarks }}
                  </div>
                </td>
                <td
                  class="hidden px-1 py-5 text-right text-sm text-gray-500 dark:text-gray-100 sm:table-cell"
                >
                  {{ formattedDob(transfer.start_date) }}
                </td>
                <td
                  class="hidden px-1 py-5 text-right text-sm text-gray-500 dark:text-gray-100 sm:table-cell"
                >
                  {{ formattedDob(transfer.end_date) }}
                </td>
              </tr>
            </tbody>
          </table>
          <div
            v-else
            class="px-4 py-6 text-sm font-bold text-gray-400 dark:text-gray-100 tracking-wider text-center"
          >
            No transfers found.
          </div>
        </div>
      </dl>
    </div>
    <Modal @close="toggleTransferModal()" :show="openTransferModal">
      <Transfer
        @formSubmitted="toggleTransferModal()"
        :staff="staff"
        :institution="institution"
        :transfers="transfers"
      />
    </Modal>
  </main>
</template>
