<script setup>
import { addYears, subYears, format } from "date-fns";
const today = new Date();
</script>
<template>
	<FormKit
		id="hire_date"
		type="date"
		name="hire_date"
		:min="format(subYears(today, 80), 'yyyy-MM-dd')"
		:max="format(addYears(today, 2), 'yyyy-MM-dd')"
		:value="format(new Date(), 'yyyy-MM-dd')"
		label="Date of Employment"
		:validation="
			'required|date_before:' +
			addYears(today, 2) +
			'|date_after:' +
			subYears(today, 80)
		"
		validation-visibility="submit"
	/>

	<FormKit
		id="file_number"
		type="text"
		name="file_number"
		label="File number"
		placeholder="File number"
		validation="required|length:2,10"
	/>

	<FormKit
		id="staff_number"
		type="text"
		name="staff_number"
		label="Staff number"
		placeholder="Staff number"
		validation="required|length:2,10"
	/>

	<!-- <FormKit
        type="textarea"
        name="remarks"
        id="remarks"
        label="Remarks"
        placeholder="Remarks"
        validation="length:2,200"
    /> -->
</template>
