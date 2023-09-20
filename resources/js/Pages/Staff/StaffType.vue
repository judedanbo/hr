<script setup>
import { differenceInYears } from "date-fns";
import ChangeStaffType from "./partials/ChangeStaffType.vue";
import Modal from "@/Components/Modal.vue";
import { ref, watch } from "vue";
import { useToggle } from "@vueuse/core";

const emit = defineEmits(["closeForm"]);

let props = defineProps({
  types: Array,
  staff: Number,
  institution: Number,
});

let openStaffTypeModal = ref(false);
const toggleStaffTypeModal = useToggle(openStaffTypeModal);


</script>
<template>
  <!-- Transfer History -->
  <main>
    <h2 class="sr-only">Staff Type</h2>
    <div
      class="rounded-lg bg-gray-50 dark:bg-gray-500 shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-600/80  "
    >
      <dl class="flex flex-wrap">
        <div class="flex-auto pl-6 pt-6">
          <dt
            class="text-md tracking-wide font-semibold leading-6 text-gray-900 dark:text-gray-50"
          >
            Staff Type
          </dt>
        </div>
        <div class="flex-none self-end px-6 pt-4 ">
          <button
            @click="toggleStaffTypeModal()"
            class="rounded-md bg-green-50 dark:bg-gray-400 px-2 py-1 text-xs font-medium text-green-600 dark:text-gray-50 ring-1 ring-inset ring-green-600/20 dark:ring-gray-500"
          >
            {{  "Change" }}
          </button>
        </div>

        <div class="-mx-4 flow-root sm:mx-0 w-full p-4 overflow-y-auto">
          <table v-if="types.length > 0" class="min-w-full">
            <colgroup></colgroup>
            <thead
              class="border-b border-gray-300 text-gray-900 dark:border-gray-200/30 dark:text-gray-50"
            >
              <tr>
                <th
                  scope="col"
                  class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 dark:text-gray-50 sm:pl-0"
                >
                  Type
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
              </tr>
            </thead>
            <tbody >
              <tr
                v-for="type in types"
                :key="type.id"
                class="border-b border-gray-200 dark:border-gray-400/30"
              >
                <td class="max-w-0 py-2 pl-1 pr-3 text-xs sm:pl-0">
                  <div class="font-medium text-gray-900 dark:text-gray-50 w-3/5">
                    {{ type.type_label }}
                  </div>
                  
                </td>
                <td
                  class="hidden px-1 py-5 text-right text-xs text-gray-500 dark:text-gray-100 sm:table-cell w-1/5"
                >
                  {{ type.start_date }}
                </td>
                <td
                  class="hidden px-1 py-5 text-right text-xs text-gray-500 dark:text-gray-100 sm:table-cell w-1/5"
                >
                  {{ type.end_date }}
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
    <Modal @close="toggleStaffTypeModal()" :show="openStaffTypeModal">
      <ChangeStaffType
        @formSubmitted="toggleStaffTypeModal()"
        :staff="staff"
        :institution="institution"
      />
    </Modal>
  </main>
</template>
