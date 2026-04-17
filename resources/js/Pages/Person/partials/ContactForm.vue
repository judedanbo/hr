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
		id="contact_type"
		type="select"
		name="contact_type"
		label="Contact type"
		placeholder="Select one"
		validation="required"
		:options="contact_types"
		outer-class="w-full"
		input-class="w-full"
	/>
	<FormKit
		id="contact"
		type="text"
		name="contact"
		label="Contact"
		placeholder="Contact"
		validation="required|length:2,100"
		outer-class="w-full"
		input-class="w-full"
	/>
</template>

<style scoped>
:deep(.formkit-outer) {
	width: 100%;
}
:deep(.formkit-inner),
:deep(.formkit-input) {
	width: 100%;
}
</style>
