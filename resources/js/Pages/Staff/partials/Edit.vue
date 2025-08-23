<script setup>
import { router } from "@inertiajs/vue3";
import { onMounted, ref } from "vue";
const emit = defineEmits(["formSubmitted"]);

const props = defineProps({
	model: Object,
	staff: Number,
	institution: Number,
});

import { format, addDays, subYears } from "date-fns";

const today = format(new Date(), "yyyy-MM-dd");
const start_date = format(addDays(new Date(), 1), "yyyy-MM-dd");
const end_date = format(subYears(new Date(), 4), "yyyy-MM-dd");

let ranks = ref([]);

onMounted(async () => {
	const response = await axios.get(
		route("institution.job-list", { institution: props.institution }),
	);
	ranks.value = response.data;
});

const submitHandler = (data, node) => {
	router.patch(
		route("staff.promote.update", {
			staff: data.staff_id,
			promotion: data.id,
		}),
		data,
		{
			preserveScroll: true,
			onSuccess: () => {
				node.reset();
				emit("formSubmitted");
			},
			onError: (errors) => {
				node.setErrors([""], errors);
			},
		},
	);
};
</script>

<template>
	<main class="px-8 py-8 bg-gray-100 dark:bg-gray-700">
		<h1 class="text-2xl pb-4 dark:text-gray-100">Edit Staff promotion</h1>

		<FormKit
			type="form"
			submit-label="Save"
			:value="{
				rank_id: model.rank_id,
				start_date: model.start_date_unix,
				end_date: model.end_date_unix,
				remarks: model.remarks,
				staff_id: model.staff_id,
			}"
			@submit="submitHandler"
		>
			<FormKit type="hidden" name="id" :value="model.id" />
			<FormKit type="hidden" name="staff_id" :value="model.staff_id" />
			<FormKit
				id="rank_id"
				type="select"
				name="rank_id"
				validation="required|integer|min:1|max:2000"
				label="New Rank"
				placeholder="Select new Rank"
				:options="ranks"
				error-visibility="submit"
			>
			</FormKit>
			<div class="sm:flex gap-4">
				<FormKit
					id="start_date"
					type="date"
					name="start_date"
					label="Start date"
					validation-visibility="submit"
				/>
				<FormKit
					id="end_date"
					type="date"
					name="end_date"
					:value="today"
					label="End date"
					validation-visibility="submit"
				/>
			</div>
			<FormKit
				id="remarks"
				type="text"
				name="remarks"
				label="Remarks"
				validation="string"
				validation-visibility="submit"
			/>
		</FormKit>
	</main>
</template>
