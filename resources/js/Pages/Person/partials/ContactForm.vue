<script setup>
import { onMounted, ref } from "vue";
const contact_types = ref([]);

onMounted(async () => {
	const { data } = await axios.get(route("contact-type.index"));
	contact_types.value = data;
});
</script>
<template>
	<FormKit
		type="select"
		name="contact_type"
		id="contact_type"
		label="Contact type"
		placeholder="Select one"
		validation="required"
		:options="contact_types"
	/>
	<FormKit
		type="text"
		name="contact"
		id="contact"
		label="Contact"
		placeholder="Contact"
		validation="required|length:2,50"
	/>
</template>
