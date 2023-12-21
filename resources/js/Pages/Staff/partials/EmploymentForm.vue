<script setup>
import { addYears, subYears, format } from "date-fns";
const today = new Date();
</script>
<template>
	<FormKit
		type="date"
		name="hire_date"
		id="hire_date"
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
		type="text"
		name="file_number"
		id="file_number"
		label="File number"
		placeholder="File number"
		validation="required|length:2,10"
	/>

	<FormKit
		type="text"
		name="staff_number"
		id="staff_number"
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
