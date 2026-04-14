<script setup>
import { router } from "@inertiajs/vue3";
import { ref, onMounted } from "vue";
import axios from "axios";
import { ArrowPathIcon } from "@heroicons/vue/20/solid";

const emit = defineEmits(["formSubmitted"]);

defineProps({
	person: Number,
});

const qualificationLevels = ref([]);
const isSubmitting = ref(false);

onMounted(async () => {
	const { data } = await axios.get(route("qualification-level.index"));
	qualificationLevels.value = data;
});

const submitHandler = (data, node) => {
	isSubmitting.value = true;
	router.post(route("qualification.store"), data, {
		preserveScroll: true,
		onSuccess: () => {
			node.reset();
			emit("formSubmitted");
		},
		onError: (errors) => {
			node.setErrors([""], errors);
		},
		onFinish: () => {
			isSubmitting.value = false;
		},
	});
};
</script>

<template>
	<main class="px-8 py-8 bg-gray-100 dark:bg-gray-700">
		<h1 class="text-2xl pb-4 dark:text-gray-100">Add Qualification</h1>
		<FormKit
			type="form"
			:disabled="isSubmitting"
			@submit="submitHandler"
		>
			<FormKit id="person_id" type="hidden" name="person_id" :value="person" />
			<FormKit
				id="institution"
				type="text"
				name="institution"
				label="Institution"
				validation="string|length:2,100"
				validation-visibility="submit"
			/>
			<div class="sm:flex gap-4">
				<FormKit
					id="course"
					type="text"
					name="course"
					label="Course"
					validation="required|string|length:2,100"
					validation-visibility="submit"
				/>
				<FormKit
					id="level"
					type="select"
					name="level"
					label="Level"
					placeholder="Select level"
					:options="
						qualificationLevels.map((l) => ({
							label: l.label,
							value: l.value,
						}))
					"
					validation-visibility="submit"
				/>
			</div>
			<div class="sm:flex gap-4">
				<FormKit
					id="qualification"
					type="text"
					name="qualification"
					label="Qualification"
					validation="string|length:2,100"
					validation-visibility="submit"
				/>
				<div>
					<FormKit
						id="qualification_number"
						type="text"
						name="qualification_number"
						label="Qualification Number"
						validation="string|length:2,100"
						validation-visibility="submit"
					/>
				</div>
			</div>
			<div class="w-1/2 sm:w-1/3 xl:w-1/4">
				<FormKit
					id="year"
					type="text"
					name="year"
					label="Year of Graduation"
					validation="string|length:2,100"
					validation-visibility="submit"
				/>
			</div>

			<!-- Document upload info -->
			<div
				class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/30 rounded-md border border-blue-200 dark:border-blue-800"
			>
				<p class="text-sm text-blue-700 dark:text-blue-300">
					<span class="font-medium">Note:</span> You can upload supporting
					documents (certificates, transcripts) after saving this qualification
					by editing it.
				</p>
			</div>

			<!-- Custom submit button with loading state -->
			<template #submit>
				<button
					type="submit"
					:disabled="isSubmitting"
					class="mt-4 inline-flex items-center justify-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-sm text-white tracking-widest hover:bg-green-500 active:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150 disabled:opacity-50 disabled:cursor-not-allowed"
				>
					<ArrowPathIcon
						v-if="isSubmitting"
						class="w-4 h-4 mr-2 animate-spin"
					/>
					{{ isSubmitting ? "Saving..." : "Create" }}
				</button>
			</template>
		</FormKit>
	</main>
</template>

<style scoped>
.formkit-outer {
	@apply w-full;
}
</style>
