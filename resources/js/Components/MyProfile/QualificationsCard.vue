<script setup>
import { ref, computed } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { useToggle } from "@vueuse/core";
import NewModal from "@/Components/NewModal.vue";
import AddQualification from "@/Pages/Qualification/Add.vue";
import EditQualification from "@/Pages/Qualification/Edit.vue";
import DeleteQualification from "@/Pages/Qualification/Delete.vue";
import AttachDocument from "@/Pages/Qualification/AttachDocument.vue";

const props = defineProps({
	qualifications: { type: Array, default: () => [] },
	person: { type: Object, required: true },
});

const page = usePage();
const permissions = computed(() => page.props?.auth?.permissions ?? []);
const canAdd = computed(
	() =>
		permissions.value.includes("create staff qualification") ||
		permissions.value.includes("update staff"),
);
const canExport = computed(() =>
	permissions.value.includes("qualifications.reports.export"),
);

const openAdd = ref(false);
const openEdit = ref(false);
const openDelete = ref(false);
const openAttach = ref(false);
const toggleAdd = useToggle(openAdd);
const toggleEdit = useToggle(openEdit);
const toggleDelete = useToggle(openDelete);
const toggleAttach = useToggle(openAttach);

const current = ref(null);

function startEdit(q) {
	current.value = q;
	toggleEdit();
}
function startDelete(q) {
	current.value = q;
	toggleDelete();
}
function startAttach(q) {
	current.value = q;
	toggleAttach();
}

function confirmDelete() {
	router.delete(
		route("qualification.delete", { qualification: current.value.id }),
		{
			preserveScroll: true,
			onSuccess: () => {
				current.value = null;
				toggleDelete();
				router.reload({ only: ["qualifications"] });
			},
		},
	);
}

function statusTag(status) {
	const tone = (status ?? "").toLowerCase();
	if (tone.includes("approved")) {
		return "bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200";
	}
	if (tone.includes("pending")) {
		return "bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200";
	}
	return "bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200";
}
</script>

<template>
	<section
		class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl p-5 sm:p-6 shadow-sm"
	>
		<header class="flex justify-between items-start mb-4">
			<div>
				<h2 class="text-base font-bold text-gray-900 dark:text-gray-50">
					Your qualifications
				</h2>
				<p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
					{{
						qualifications.length === 0
							? "Degrees, certificates, training. Reviewed by HR."
							: `${qualifications.length} added`
					}}
				</p>
			</div>
			<div class="flex items-center gap-2">
				<a
					v-if="canExport"
					:href="route('qualifications.reports.staff.profile.pdf', person.id)"
					class="text-[11px] font-semibold px-2.5 py-1 rounded-full bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-200"
				>PDF</a>
				<span
					v-if="qualifications.length === 0"
					class="text-[11px] font-semibold px-2.5 py-1 rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200"
				>0 added</span>
				<span
					v-else
					class="text-[11px] font-semibold px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200"
				>&#10003; Active</span>
			</div>
		</header>

		<!-- EMPTY STATE -->
		<div
			v-if="qualifications.length === 0"
			class="rounded-xl border-2 border-dashed border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-900/40 px-6 py-9 text-center"
		>
			<div class="text-4xl">&#127891;</div>
			<p class="mt-2 text-sm font-bold text-gray-900 dark:text-gray-100">
				Add your first qualification
			</p>
			<p class="mt-1 text-xs text-gray-600 dark:text-gray-400">
				We'll walk you through it — name, institution, year, and an optional
				certificate upload.
			</p>
			<button
				v-if="canAdd"
				type="button"
				class="mt-4 w-full rounded-lg bg-emerald-600 hover:bg-emerald-700 px-4 py-2.5 text-sm font-bold text-white shadow-sm"
				@click="toggleAdd()"
			>
				+ Add qualification
			</button>
			<div class="flex flex-wrap justify-center gap-1.5 mt-3">
				<span
					v-for="tag in ['Degree', 'Diploma', 'Certificate', 'Training']"
					:key="tag"
					class="px-2.5 py-0.5 rounded-full bg-gray-100 dark:bg-gray-700 text-[11px] text-gray-600 dark:text-gray-300"
				>{{ tag }}</span>
			</div>
		</div>

		<!-- FILLED STATE -->
		<ul v-else class="space-y-2">
			<li
				v-for="q in qualifications"
				:key="q.id"
				class="flex items-center gap-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 px-3 py-2.5"
			>
				<span
					class="w-9 h-9 rounded-md bg-emerald-50 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-200 flex items-center justify-center text-base"
				>&#127891;</span>
				<div class="flex-1 min-w-0">
					<div
						class="text-sm font-bold truncate text-gray-900 dark:text-gray-50"
					>
						{{ q.qualification || q.course }}
					</div>
					<div
						class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5 flex flex-wrap items-center gap-x-1.5"
					>
						<span v-if="q.institution">{{ q.institution }}</span>
						<span v-if="q.year">&#183; {{ q.year }}</span>
						<span
							v-if="q.status"
							:class="[
								'px-2 py-0.5 rounded-full text-[10px] font-semibold',
								statusTag(q.status),
							]"
						>{{ q.status }}</span>
						<span v-if="q.documents" class="text-gray-500"
							>&#183; {{ q.documents.length }}
							document{{ q.documents.length === 1 ? "" : "s" }}</span
						>
					</div>
				</div>
				<div
					class="flex items-center gap-2 text-[11px] text-emerald-700 dark:text-emerald-300 font-semibold"
				>
					<button
						v-if="q.can_edit"
						type="button"
						class="hover:underline"
						@click="startAttach(q)"
					>
						Attach
					</button>
					<button
						v-if="q.can_edit"
						type="button"
						class="hover:underline"
						@click="startEdit(q)"
					>
						Edit
					</button>
					<button
						v-if="q.can_delete"
						type="button"
						class="hover:underline text-red-600 dark:text-red-400"
						@click="startDelete(q)"
					>
						Delete
					</button>
				</div>
			</li>
			<li>
				<button
					v-if="canAdd"
					type="button"
					class="w-full rounded-lg border border-dashed border-emerald-500 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-200 px-3 py-2.5 text-sm font-semibold hover:bg-emerald-100"
					@click="toggleAdd()"
				>
					+ Add another qualification
				</button>
			</li>
		</ul>

		<!-- Add Qualification Modal -->
		<NewModal :show="openAdd" @close="toggleAdd()">
			<AddQualification
				:person="person.id"
				@form-submitted="
					() => {
						toggleAdd();
						router.reload({ only: ['qualifications'] });
					}
				"
			/>
		</NewModal>

		<!-- Edit Qualification Modal -->
		<NewModal :show="openEdit" @close="toggleEdit()">
			<EditQualification
				v-if="current"
				:qualification="current"
				@form-submitted="
					() => {
						toggleEdit();
						router.reload({ only: ['qualifications'] });
					}
				"
			/>
		</NewModal>

		<!-- Delete Qualification Modal -->
		<NewModal :show="openDelete" @close="toggleDelete()">
			<DeleteQualification
				:person="person.name"
				@close="toggleDelete()"
				@delete-confirmed="confirmDelete"
			/>
		</NewModal>

		<!-- Attach Document Modal -->
		<NewModal :show="openAttach" @close="toggleAttach()">
			<AttachDocument
				v-if="current"
				:qualification="current"
				@close="toggleAttach()"
				@form-submitted="
					() => {
						toggleAttach();
						router.reload({ only: ['qualifications'] });
					}
				"
			/>
		</NewModal>
	</section>
</template>
