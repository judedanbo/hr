<script setup>
defineProps({
	staffId: {
		type: Number,
		required: true,
	},
});
</script>
<template>
	<FormKit
		type="hidden"
		name="staff_id"
		id="staff_id"
		:value="staffId"
		validation="required|min:1|max:90000"
	/>
	<!-- <FormKit v-if="new"
    type="text"
    name="person_id"
    id="person_id"
    :value="person?.id"
    validation="required|min:1|max:90000"/> -->
	<FormKit
		type="text"
		name="relation"
		id="relation"
		label="Relation to Staff"
		placeholder="Relation to staff"
		validation="required|length:2,40"
	/>
</template>
