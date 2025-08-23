<script setup>
import { ref, onMounted } from "vue";
import { format, addDays, subYears, addYears } from "date-fns";

const today = format(new Date(), "yyyy-MM-dd");
const min_date = format(subYears(new Date(), 2), "yyyy-MM-dd");
const max_date = format(addYears(new Date(), 2), "yyyy-MM-dd");
let input_start_date = ref(today)

const props = defineProps({
	institution: {
		type: Number,
		required: true,
	},
});
let units = ref([]);

onMounted(async () => {
	const response = await axios.get(
		route("institution.unit-list", { institution: props.institution }),
	);
	units.value = response.data;
});
</script>
<template>
	<FormKit
		id="unit_id"
		name="unit_id"
		type="select"
		validation="integer|min:1|max:300"
		label="New Location"
		placeholder="Select new location"
		:options="units"
		error-visibility="submit"
	/>
	<div class="flex gap-4">

		<FormKit
			id="start_date"
			v-model="input_start_date"
			name="start_date"
			type="date"
			:max="max_date"
			label="Start date"
			:validation="
				'required|date_after:' + min_date
			"
			validation-visibility="submit"
			outer-class="flex-1"
		/>
		<FormKit
			id="end_date"
			type="date"
			name="end_date"
			:min="input_start_date"
			:max="max_date"
			label="End date"
			:validation="
				'date_after:' + input_start_date + '|date_before:' + max_date
			"
			validation-visibility="submit"
			outer-class="flex-1"
			/>
	</div>
	<FormKit
		id="remarks"
		name="remarks"
		type="text"
		label="Remarks"
		validation="string|length:2,120"
		validation-visibility="submit"
	/>
</template>
