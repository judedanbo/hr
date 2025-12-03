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

const emit = defineEmits(["viewNote", "editNote", "deleteNote"]);

const props = defineProps({
    notes: { type: Array, required: true },
});

const page = usePage();
const permissions = computed(() => page.props?.auth.permissions);

const subMenuClicked = (action, note) => {
    if (action === "View") emit("viewNote", note);
    if (action === "Edit") emit("editNote", note);
    if (action === "Delete") emit("deleteNote", note);
};

const getNoteTypeBadgeClass = (noteType) => {
    if (!noteType) return "bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300";

    // Separation-related types
    if (["RET", "DEC", "RES", "DIS", "TER"].includes(noteType)) {
        return "bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300";
    }
    // Leave-related types
    if (["LWP", "SIC", "ANN", "MAT", "STU", "SAB"].includes(noteType)) {
        return "bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300";
    }
    // Interdiction/suspension
    if (["INT", "SUS"].includes(noteType)) {
        return "bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300";
    }
    return "bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300";
};
</script>

<template>
    <section class="flex flex-col mt-6 -my-2 overflow-x-auto">
        <div class="inline-block min-w-full py-2 align-middle">
            <MainTable v-if="notes.length > 0">
                <TableHead>
                    <RowHeader>Date</RowHeader>
                    <RowHeader>Type</RowHeader>
                    <RowHeader>Note</RowHeader>
                    <RowHeader>Staff</RowHeader>
                    <RowHeader>Docs</RowHeader>
                    <RowHeader>Actions</RowHeader>
                </TableHead>
                <TableBody>
                    <template v-for="note in notes" :key="note.id">
                        <TableRow>
                            <TableData>
                                <span class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ note.note_date || "N/A" }}
                                </span>
                            </TableData>
                            <TableData>
                                <span
                                    v-if="note.note_type_label"
                                    :class="[
                                        'px-2 py-1 text-xs font-medium rounded-full',
                                        getNoteTypeBadgeClass(note.note_type),
                                    ]"
                                >
                                    {{ note.note_type_label }}
                                </span>
                                <span v-else class="text-sm text-gray-400">-</span>
                            </TableData>
                            <TableData>
                                <span class="text-sm dark:text-gray-300 line-clamp-2">
                                    {{ note.note }}
                                </span>
                            </TableData>
                            <TableData>
                                <span class="text-sm dark:text-gray-300">
                                    {{ note.notable_name || "-" }}
                                </span>
                            </TableData>
                            <TableData>
                                <span
                                    v-if="note.documents_count > 0"
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300"
                                >
                                    {{ note.documents_count }}
                                </span>
                                <span v-else class="text-sm text-gray-400">0</span>
                            </TableData>
                            <TableData>
                                <SubMenu
                                    :items="['View', 'Edit', 'Delete']"
                                    :can-edit="permissions?.includes('edit staff notes')"
                                    :can-delete="permissions?.includes('edit staff notes')"
                                    @item-clicked="(action) => subMenuClicked(action, note)"
                                />
                            </TableData>
                        </TableRow>
                    </template>
                </TableBody>
            </MainTable>
            <NoItem v-else name="Notes" />
            <slot name="pagination" />
        </div>
    </section>
</template>
