<script setup>
import { ref, onMounted } from "vue";

const country = ref([]);
onMounted(async () => {
	const countryData = await axios.get(route("country.index"));
	country.value = countryData.data;
});
</script>
<template>
	<FormKit
		id="address_line_1"
		type="text"
		name="address_line_1"
		label="Address line 1"
		validation="required|string|length:2,100"
		validation-visibility="submit"
	/>
	<FormKit
		id="address_line_2"
		type="text"
		name="address_line_2"
		label="Address line 2"
		validation="string|length:2,100"
		validation-visibility="submit"
	/>
	<div class="md:flex md:gap-x-2">
		<FormKit
			id="post_code"
			type="text"
			name="post_code"
			label="Post Code"
			validation="string|length:2,100"
			validation-visibility="submit"
		/>
		<FormKit
			id="city"
			type="text"
			name="city"
			label="City"
			validation="string|length:2,100"
			validation-visibility="submit"
		/>
		<FormKit
			id="region"
			type="text"
			name="region"
			label="Region"
			validation="string|length:2,100"
			validation-visibility="submit"
			outer-class="md:w-1/2"
		/>
	</div>
	<FormKit
		id="country"
		name="country"
		type="select"
		label="Country"
		validation="required"
		placeholder="Select one"
		:options="country"
	/>
</template>
