<script setup>
import { format, differenceInYears } from "date-fns";
import { Link } from "@inertiajs/inertia-vue3";

defineProps({
  address: Array,
  contacts: Array,
});
const formattedDob = (dob) => {
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
  <!-- contact History -->
  <main>
    <h2 class="sr-only">Staff Contact Information</h2>
    <div
      class="rounded-lg bg-gray-50 dark:bg-gray-500 shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-300/80"
    >
      <dl class="flex flex-wrap">
        <div class="flex-auto pl-6 pt-6">
          <dt
            class="text-sm font-semibold leading-6 text-gray-900 dark:text-gray-100"
          >
            Staff Address
          </dt>
        </div>
        <div class="flex-none self-end px-6 pt-4">
          <Link
            :href="route('staff.index')"
            class="rounded-md bg-green-50 dark:bg-gray-400 px-2 py-1 text-xs font-medium text-green-600 dark:text-gray-50 ring-1 ring-inset ring-green-600/20 dark:ring-gray-200"
          >
            change address
          </Link>
        </div>

        <div class="-mx-4 mt-8 flow-root sm:mx-0 w-full px-4">
          
          <div
          v-if="address.address_line_1"
          >
            <dd
              class="text-sm leading-6 text-gray-500 dark:text-gray-50"
            >
              {{ address.address_line_1 ?? "Gender Not Specified" }}
            </dd>
            <dd
              class="text-sm leading-6 text-gray-500 dark:text-gray-50"
            >
              {{ address.address_line_2 ?? "Gender Not Specified" }}
            </dd>
            <dd
              class="text-sm leading-6 text-gray-500 dark:text-gray-50"
            >
              {{ address.city ?? "Gender Not Specified" }}
            </dd>
            <dd
              class="text-sm leading-6 text-gray-500 dark:text-gray-50"
            >
              {{ address.region ?? "Gender Not Specified" }}
            </dd>
            <dd
              class="text-sm leading-6 text-gray-500 dark:text-gray-50"
            >
              {{ address.country ?? "Gender Not Specified" }}
            </dd>
            <dd
              class="text-sm leading-6 text-gray-500 dark:text-gray-50"
            >
              {{ address.post_code ?? "Gender Not Specified" }}
            </dd>
          </div>
          <div
            v-else
            class="px-4 py-6 text-sm font-bold text-gray-400 dark:text-gray-100 tracking-wider text-center"
          >
            No address found.
          </div>
        </div>
      </dl>
      <dl class="flex flex-wrap">
        <div class="flex-auto pl-6 pt-6">
          <dt
            class="text-sm font-semibold leading-6 text-gray-900 dark:text-gray-100"
          >
            Staff Contact Information
          </dt>
        </div>
        <div class="flex-none self-end px-6 pt-4">
          <Link
            :href="route('staff.index')"
            class="rounded-md bg-green-50 dark:bg-gray-400 px-2 py-1 text-xs font-medium text-green-600 dark:text-gray-50 ring-1 ring-inset ring-green-600/20 dark:ring-gray-200"
          >
            Add Contact
          </Link>
        </div>

        <div class="-mx-4 mt-8 flow-root sm:mx-0 w-full px-4">
          <table v-if="contacts" class="min-w-full">
            <colgroup>
              <col class="w-full" />
              <col class="sm:w-1/6" />
            </colgroup>
            <thead class="border-b border-gray-300 text-gray-900">
              <tr>
                <th
                  scope="col"
                  class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 dark:text-gray-100 sm:pl-0"
                >
                  Contact type
                </th>
                <th
                  scope="col"
                  class="hidden px-3 py-3.5 text-right text-sm font-semibold text-gray-900 dark:text-gray-100 sm:table-cell"
                >
                  Details
                </th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="contact in contacts"
                :key="contact.id"
                class="border-b border-gray-200"
              >
                <td class="max-w-0 py-5 pl-4 pr-3 text-sm sm:pl-0">
                  <div class="font-medium text-gray-900 dark:text-gray-100">
                    {{ contact.contact_type }}
                  </div>
                </td>
                <td
                  class="hidden px-3 py-5 text-right text-sm text-gray-500 dark:text-gray-100 sm:table-cell"
                >
                  {{ contact.contact }}
                </td>
              </tr>
            </tbody>
          </table>
          <div
            v-else
            class="px-4 py-6 text-sm font-bold text-gray-400 dark:text-gray-100 tracking-wider text-center"
          >
            No contacts found.
          </div>
        </div>
      </dl>
    </div>
  </main>
</template>
