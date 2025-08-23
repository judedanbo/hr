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
		id="staff_id"
		type="hidden"
		name="staff_id"
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
		id="relation"
		type="text"
		name="relation"
		label="Relation to Staff"
		placeholder="Relation to staff"
		validation="required|length:2,40"
	/>
</template>
