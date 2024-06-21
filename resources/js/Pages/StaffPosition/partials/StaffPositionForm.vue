<script setup>
import { ref, onMounted } from "vue";
import { format, addDays, subYears, addYears } from "date-fns";

const today = format(new Date(), "yyyy-MM-dd");
const start_date = format(addDays(new Date(), 1), "yyyy-MM-dd");
const end_date = format(addYears(new Date(), 3), "yyyy-MM-dd");

const props = defineProps({
	institution: { type: Number, required: true },
});
let positions = ref([]);

onMounted(async () => {
	const response = await axios.get(
		route("position.list", { institution: props.institution }),
	);
	positions.value = response.data;
});
</script>
<template>
	<FormKit
		id="position_id"
		type="select"
		name="position_id"
		validation="required|string"
		label="Staff position"
		placeholder="Select staff position"
		:options="positions"
		error-visibility="submit"
	/>
	<div class="sm:flex gap-4">
		<FormKit
			id="start_date"
			type="date"
			name="start_date"
			:max="today"
			label="Start date"
			:value="today"
			:validation="'date_before:' + start_date"
			validation-visibility="submit"
			outer-class="sm:flex-1"
		/>
		<FormKit
			id="end_date"
			type="date"
			name="end_date"
			:max="end_date"
			label="End date"
			:validation="'date_before:' + end_date"
			validation-visibility="submit"
			outer-class="sm:flex-1"
		/>
	</div>
</template>
