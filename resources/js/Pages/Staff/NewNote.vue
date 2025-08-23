<script setup>
import { PaperClipIcon } from "@heroicons/vue/20/solid";
import { router } from "@inertiajs/vue3";
import { ref, onMounted } from "vue";
import NewSelect from "@/Components/NewSelect.vue";
import Avatar from "@/Components/Avatar.vue";
const emit = defineEmits(["noteAdded"]);

const props = defineProps({
	notable_id: Number,
	notable_type: String,
	user: Object,
});

const noteTypes = ref(null);
onMounted(async () => {
	const noteTypesData = await axios.get(route("note-types"));
	noteTypes.value = noteTypesData.data;
});

const form = ref({
	note: "",
	note_type: "",
	notable_id: props.notable_id,
	notable_type: props.notable_type,
	document: "",
});

// const documentChanged = () => {
// 	const file = noteDocument.files[0];
// };

const saveNote = () => {
	// console.log(form.value);
	// router.post(route("notes.store"), form.value, {
	router.post(
		route("staff.write-note", { staff: props.notable_id }),
		form.value,
		{
			preserveScroll: true,
			onSuccess: (response) => {
				// emit("noteAdded");
				form.value.note_type = "";
				form.value.note = "";

				form.value.note = "";
			},
			onError: (error) => {},
		},
	);
};
</script>
<template>
	<!-- {{ form }} -->
	<div class="mt-4 flex gap-x-3">
		<!-- <Avatar :person_id="props.user.id" /> -->

		<form class="relative flex-auto" @submit.prevent="saveNote()">
			<div
				class="overflow-hidden rounded-lg pb-12 shadow-sm ring-1 ring-inset ring-gray-200 dark:ring-gray-400 focus-within:ring-2 focus-within:ring-green-500 dark:focus-within:ring-gray-200"
			>
				<label for="note" class="sr-only">Add note</label>
				<textarea
					id="note"
					v-model="form.note"
					rows="2"
					name="note"
					class="block w-full resize-none border-0 bg-transparent py-1.5 text-gray-900 dark:text-gray-50 placeholder:text-gray-400 dark:placeholder:text-gray-200 focus:ring-0 sm:text-sm sm:leading-6"
					placeholder="Add note..."
				/>
			</div>

			<div
				class="absolute inset-x-0 bottom-0 flex justify-between py-2 pl-3 pr-2"
			>
				<div class="flex items-center space-x-5">
					<div class="flex items-center">
						<FormKit
							id="noteDocument"
							v-model="form.document"
							name="document"
							type="file"
							accept=".png, jpeg, jpg, .pdf, .doc, .docx"
							validation="document"
							multiple="true"
						></FormKit>
						<!-- <button
							type="button"
							class="mx-1 flex h-10 w-10 items-center justify-center rounded-full text-gray-400 dark:text-gray-50 hover:text-gray-500 dark:hover:text-gray-200"
						>
							<PaperClipIcon class="h-5 w-5 text" aria-hidden="true" />
							<span class="sr-only">Attach a file</span>
						</button> -->
					</div>
					<div class="flex items-center w-56">
						<NewSelect
							v-if="noteTypes"
							v-model="form.note_type"
							:options="noteTypes"
							class=""
						/>
					</div>
				</div>
				<button
					type="submit"
					class="rounded-md bg-white dark:bg-gray-600 px-2.5 py-1.5 text-sm font-semibold text-gray-900 dark:text-white shadow-sm ring-1 ring-inset ring-gray-300 focus-within:ring-green-500 hover:bg-gray-50"
				>
					Save note
				</button>
			</div>
		</form>
	</div>
</template>
