<script setup>
import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import MainTable from "@/Components/Table/MainTable.vue";
import TableHead from "@/Components/Table/TableHead.vue";
import TableBody from "@/Components/Table/TableBody.vue";
import TableRow from "@/Components/Table/TableRow.vue";
import TableData from "@/Components/Table/TableData.vue";
import RowHeader from "@/Components/Table/RowHeader.vue";
import NoItem from "@/Components/NoItem.vue";
import SubMenu from "@/Components/SubMenu.vue";

const emit = defineEmits(["editContact", "deleteContact"]);
const props = defineProps({ contacts: { type: Array, required: true } });

const page = usePage();
const permissions = computed(() => page.props?.auth.permissions);

const subMenuClicked = (action, contact) => {
    if (action === "Edit") emit("editContact", contact);
    if (action === "Delete") emit("deleteContact", contact);
};
</script>

<template>
    <section class="flex flex-col mt-6 -my-2 overflow-x-auto">
        <div class="inline-block min-w-full py-2 align-middle">
            <MainTable v-if="contacts.length > 0">
                <TableHead>
                    <RowHeader>Person</RowHeader>
                    <RowHeader>Type</RowHeader>
                    <RowHeader>Contact</RowHeader>
                    <RowHeader>Valid Until</RowHeader>
                    <RowHeader>Status</RowHeader>
                    <RowHeader>Actions</RowHeader>
                </TableHead>
                <TableBody>
                    <TableRow v-for="contact in contacts" :key="contact.id">
                        <TableData><span class="text-sm dark:text-gray-300">{{ contact.person_name }}</span></TableData>
                        <TableData><span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">{{ contact.contact_type_label }}</span></TableData>
                        <TableData><span class="text-sm dark:text-gray-300">{{ contact.contact }}</span></TableData>
                        <TableData><span class="text-sm text-gray-600 dark:text-gray-400">{{ contact.valid_end || "N/A" }}</span></TableData>
                        <TableData>
                            <span :class="['px-2 py-1 text-xs font-medium rounded-full', contact.is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300']">
                                {{ contact.is_active ? "Active" : "Expired" }}
                            </span>
                        </TableData>
                        <TableData>
                            <SubMenu :items="['Edit', 'Delete']" :can-edit="permissions?.includes('update contacts')" :can-delete="permissions?.includes('delete contacts')" @item-clicked="(action) => subMenuClicked(action, contact)" />
                        </TableData>
                    </TableRow>
                </TableBody>
            </MainTable>
            <NoItem v-else name="Contacts" />
            <slot name="pagination" />
        </div>
    </section>
</template>
