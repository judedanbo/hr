<script setup>
import { ref, onMounted } from "vue";
const props = defineProps({
	institution: Number,
});
let statuses = ref([]);

onMounted(async () => {
	const response = await axios.get(
		route("institution.statuses", { institution: props.institution }),
	);
	statuses.value = response.data;
});
import { format, addDays, subYears, addYears } from "date-fns";

const today = format(new Date(), "yyyy-MM-dd");
const start_date = format(addDays(new Date(), 1), "yyyy-MM-dd");
const end_date = format(addYears(new Date(), 3), "yyyy-MM-dd");
</script>
<template>
	<FormKit
		type="select"
		name="status"
		id="status"
		validation="required|string"
		label="Status"
		placeholder="Select Status"
		:options="statuses"
		error-visibility="submit"
	/>
	<div class="sm:flex gap-4">
		<FormKit
			type="date"
			name="start_date"
			id="start_date"
			:max="today"
			label="Start date"
			:validation="'required|date_before:' + end_date"
			validation-visibility="submit"
			outer-class="md:flex-1"
		/>
		<FormKit
			type="date"
			name="end_date"
			id="end_date"
			:max="end_date"
			label="End date"
			:validation="'date_before:' + end_date"
			validation-visibility="submit"
			outer-class="md:flex-1"
		/>
	</div>
	<FormKit
		type="text"
		name="description"
		id="description"
		label="Description"
		validation="string|length:2,120"
		validation-visibility="submit"
	/>
</template>
