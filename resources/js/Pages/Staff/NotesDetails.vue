<script setup>
import { ref } from 'vue';
import Avatar from "@/Components/Avatar.vue";
import { PaperClipIcon } from "@heroicons/vue/20/solid";
import { EyeIcon } from '@heroicons/vue/24/outline';
import NoteDetailsModal from '@/Components/NoteDetailsModal.vue';

defineProps({
	notes: Array,
});

const showModal = ref(false);
const selectedNote = ref(null);

function viewNote(note) {
	selectedNote.value = note;
	showModal.value = true;
}

function closeModal() {
	showModal.value = false;
	selectedNote.value = null;
}
</script>
<template>
	<!-- New note form -->

	<ul role="list" class="pt-4">
		<li
			v-for="(note, noteIdx) in notes"
			:key="note.id"
			class="relative flex gap-x-4"
		>
			<div
				:class="[
					noteIdx === notes.length - 1 ? 'h-6' : '-bottom-6',
					'absolute left-0 top-0 flex w-6 justify-center',
				]"
			>
				<div class="w-px bg-gray-200" />
			</div>
			<!-- <img :src="note.person" alt="" class="relative mt-3 h-6 w-6 flex-none rounded-full bg-gray-50" /> -->
			<Avatar :person_id="note.created_by" />
			<div
				class="flex-auto rounded-md py-1 px-3 ring-1 ring-inset ring-gray-100 dark:ring-gray-400/20"
			>
				<div class="flex justify-between items-start gap-x-4">
					<time
						:datetime="note.note_date_time"
						class="flex-none py-0.5 text-xs leading-5 text-gray-500 dark:text-gray-50"
						>{{ note.note_date }}</time
					>
					<button
						@click="viewNote(note)"
						class="p-1 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
						title="View note details"
					>
						<EyeIcon class="h-5 w-5 text-gray-500 dark:text-gray-400" />
					</button>
				</div>
				<p class="text-sm leading-6 text-gray-500 dark:text-gray-50">
					{{ note.note }}
					<PaperClipIcon v-if="note.url != null || (note.documents && note.documents.length > 0)" class="w-4 h-4 inline" />
				</p>
			</div>
		</li>
	</ul>

	<!-- Note Details Modal -->
	<NoteDetailsModal
		:show="showModal"
		:note="selectedNote"
		@close="closeModal"
	/>
</template>
