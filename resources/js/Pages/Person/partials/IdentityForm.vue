<script setup>
import { onMounted, ref } from "vue";
const id_types = ref([]);

onMounted(async () => {
	const { data } = await axios.get(route("identity.index"));
	id_types.value = data;
});
</script>
<template>
	<FormKit
		id="id_type"
		type="select"
		name="id_type"
		label="Type of Identification"
		placeholder="Select one"
		validation="required"
		:options="id_types"
	/>
	<FormKit
		id="id_number"
		type="text"
		name="id_number"
		label="Identification Number"
		placeholder="GHA-123456474-4"
		validation="required|length:2,50"
	/>
</template>
