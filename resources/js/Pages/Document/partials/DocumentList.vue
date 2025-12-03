<script setup>
import { computed } from "vue";
import { usePage, Link } from "@inertiajs/vue3";
import MainTable from "@/Components/Table/MainTable.vue";
import TableHead from "@/Components/Table/TableHead.vue";
import TableBody from "@/Components/Table/TableBody.vue";
import TableRow from "@/Components/Table/TableRow.vue";
import TableData from "@/Components/Table/TableData.vue";
import RowHeader from "@/Components/Table/RowHeader.vue";
import NoItem from "@/Components/NoItem.vue";
import SubMenu from "@/Components/SubMenu.vue";

const emit = defineEmits(["editDocument", "deleteDocument"]);
const props = defineProps({ documents: { type: Array, required: true } });

const page = usePage();
const permissions = computed(() => page.props?.auth.permissions);

const subMenuClicked = (action, document) => {
    if (action === "Edit") emit("editDocument", document);
    if (action === "Delete") emit("deleteDocument", document);
};
</script>

<template>
    <section class="flex flex-col mt-6 -my-2 overflow-x-auto">
        <div class="inline-block min-w-full py-2 align-middle">
            <MainTable v-if="documents.length > 0">
                <TableHead>
                    <RowHeader>Title</RowHeader>
                    <RowHeader>Type</RowHeader>
                    <RowHeader>Number</RowHeader>
                    <RowHeader>Status</RowHeader>
                    <RowHeader>File</RowHeader>
                    <RowHeader>Created</RowHeader>
                    <RowHeader>Actions</RowHeader>
                </TableHead>
                <TableBody>
                    <TableRow v-for="document in documents" :key="document.id">
                        <TableData>
                            <Link :href="route('document.show', { document: document.id })" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                                {{ document.document_title }}
                            </Link>
                        </TableData>
                        <TableData>
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                {{ document.document_type_label }}
                            </span>
                        </TableData>
                        <TableData><span class="text-sm dark:text-gray-300">{{ document.document_number || "N/A" }}</span></TableData>
                        <TableData>
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
                                {{ document.document_status_label }}
                            </span>
                        </TableData>
                        <TableData>
                            <a v-if="document.file_name" :href="route('document.download', { document: document.id })" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                                {{ document.file_name }}
                            </a>
                            <span v-else class="text-sm text-gray-500 dark:text-gray-400">No file</span>
                        </TableData>
                        <TableData><span class="text-sm text-gray-600 dark:text-gray-400">{{ document.created_at }}</span></TableData>
                        <TableData>
                            <SubMenu :items="['Edit', 'Delete']" :can-edit="permissions?.includes('update documents')" :can-delete="permissions?.includes('delete documents')" @item-clicked="(action) => subMenuClicked(action, document)" />
                        </TableData>
                    </TableRow>
                </TableBody>
            </MainTable>
            <NoItem v-else name="Documents" />
            <slot name="pagination" />
        </div>
    </section>
</template>
