<script setup>
import { ref, onMounted } from "vue";

const nationality = ref([]);
onMounted(async () => {
	const nationalityData = await axios.get(route("nationality.index"));
	nationality.value = nationalityData.data;
});
</script>
<template>
	<FormKit
		type="text"
		name="address_line_1"
		id="address_line_1"
		label="Address line 1"
		validation="required|string|length:2,100"
		validation-visibility="submit"
	/>
	<FormKit
		type="text"
		name="address_line_2"
		id="address_line_2"
		label="Address line 2"
		validation="string|length:2,100"
		validation-visibility="submit"
	/>
	<div class="md:flex md:gap-x-2">
		<FormKit
			type="text"
			name="post_code"
			id="post_code"
			label="Post Code"
			validation="string|length:2,100"
			validation-visibility="submit"
		/>
		<FormKit
			type="text"
			name="city"
			id="city"
			label="City"
			validation="string|length:2,100"
			validation-visibility="submit"
		/>
		<FormKit
			type="text"
			name="region"
			id="region"
			label="Region"
			validation="string|length:2,100"
			validation-visibility="submit"
			outer-class="md:w-1/2"
		/>
	</div>
	<FormKit
		name="country"
		id="country"
		type="select"
		label="Country"
		validation="required"
		placeholder="Select one"
		:options="nationality"
	/>
</template>
