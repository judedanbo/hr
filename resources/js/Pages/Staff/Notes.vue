<script setup>
import { usePage } from "@inertiajs/inertia-vue3";
import NotesDetails from "./NotesDetails.vue";
import { ref, computed } from "vue";
import { useToggle } from "@vueuse/core";
import NewNote from "./NewNote.vue";

const page = usePage();
const permissions = computed(() => page.props.value.auth.permissions);

defineProps({
	notes: Array,
	notable_type: String,
	notable_id: Number,
	user: Object,
});

let openQualificationModal = ref(false);
let toggleQualificationModal = useToggle(openQualificationModal);

const formattedDob = (dob) => {
	if (!dob) return "";
	return new Date(dob).toLocaleDateString("en-GB", {
		day: "numeric",
		month: "short",
		year: "numeric",
	});
};
</script>
<template>
	<!-- Notes on staff -->
	<main>
		<h2 class="sr-only">Notes on Staff</h2>
		<div
			class="py-6 rounded-lg bg-gray-50 dark:bg-gray-500 shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-600/80"
		>
			<dl class="flex flex-wrap">
				<div class="flex-auto pl-6">
					<dt
						class="text-xl font-semibold leading-6 text-gray-900 dark:text-gray-50"
					>
						Notes
					</dt>
				</div>

				<div class="flow-root sm:mx-0 w-full px-4">
					<NewNote
						v-if="permissions.includes('create staff notes')"
						:notable_id="notable_id"
						:notable_type="notable_type"
						:user="user"
					/>
					<NotesDetails
						v-if="permissions.includes('view staff notes')"
						:notes="notes"
					/>
					<!-- <div v-else class="px-4 py-6 text-sm font-bold text-gray-400 dark:text-gray-100 tracking-wider text-center ">No notes found.</div> -->
				</div>
			</dl>
		</div>
		<!-- <Modal @close="toggleQualificationModal()" :show="openQualificationModal">
      <AddQualification @formSubmitted="toggleQualificationModal()"  :person="person" />
    </Modal> -->
	</main>
</template>
