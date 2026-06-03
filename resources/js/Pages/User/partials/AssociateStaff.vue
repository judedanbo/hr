<script setup>
import { onMounted, ref } from "vue";
import { router } from "@inertiajs/vue3";
import axios from "axios";
import SearchSelect from "@/Components/Forms/SearchSelect.vue";

const emit = defineEmits(["formSubmitted"]);

const props = defineProps({
	user: { type: Number, required: true },
});

const options = ref([]);
const selected = ref(null);
const error = ref("");
const loading = ref(false);
const loadingOptions = ref(false);

const fetchOptions = async (search = "") => {
	loadingOptions.value = true;
	try {
		const response = await axios.get(route("users.staff-options"), {
			params: { search },
		});
		options.value = response.data;
	} catch (e) {
		error.value = "Could not load staff records.";
	} finally {
		loadingOptions.value = false;
	}
};

onMounted(() => fetchOptions());

const submit = () => {
	error.value = "";
	loading.value = true;
	router.patch(
		route("user.associate-staff", { user: props.user }),
		{ person_id: selected.value },
		{
			preserveScroll: true,
			onSuccess: () => emit("formSubmitted"),
			onError: (errors) => {
				error.value = errors.person_id ?? "Could not associate staff record.";
			},
			onFinish: () => {
				loading.value = false;
			},
		},
	);
};
</script>

<template>
	<main class="px-8 py-8 bg-white dark:bg-gray-800 min-h-96">
		<h1 class="text-xl font-semibold pb-4 text-green-900 dark:text-gray-100">
			Associate Staff Record
		</h1>
		<SearchSelect
			v-model="selected"
			:options="options"
			:searchable="true"
			remote
			:loading="loadingOptions"
			max-height="max-h-96"
			label="Staff record"
			placeholder="Search staff by name or staff number"
			:error="error"
			@search="fetchOptions"
		/>
		<div class="flex justify-end pt-6">
			<button
				type="button"
				:disabled="!selected || loading"
				class="rounded-md bg-green-600 dark:bg-gray-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 disabled:opacity-50"
				@click="submit"
			>
				Associate
			</button>
		</div>
	</main>
</template>
