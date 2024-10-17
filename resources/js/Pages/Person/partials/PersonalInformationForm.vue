<script setup>
import { ref, onMounted } from "vue";
import { format, subYears, addDays } from "date-fns";

const gender = ref([]);
const maritalStatus = ref([]);
const country = ref([]);
const nationality = ref([]);
onMounted(async () => {
	const genderData = await axios.get(route("gender.index"));
	gender.value = genderData.data;
	const maritalStatusData = await axios.get(route("marital-status.index"));
	maritalStatus.value = maritalStatusData.data;

	const countryData = await axios.get(route("country.index"));
	country.value = countryData.data;

	const nationalityData = await axios.get(route("nationality.index"));
	nationality.value = nationalityData.data;
});
</script>
<template>
	<div class="md:flex md:gap-2 md:flex-wrap w-full">
		<div class="w-2/3 md:w-1/4">
			<FormKit
				type="text"
				name="title"
				id="title"
				label="Title"
				placeholder="title"
				validation-visibility="submit"
				validation="length:1,10"
			/>
		</div>
		<div class="md:flex-grow">
			<FormKit
				type="text"
				name="first_name"
				id="first_name"
				validation="required|length:2,60"
				label="First name"
				placeholder="First name"
				error-visibility="submit"
			/>
		</div>
		<div class="md:flex-grow">
			<FormKit
				type="text"
				name="surname"
				id="surname"
				validation="required|length:2,60"
				label="Surname"
				placeholder="Surname"
			/>
		</div>
		<div class="md:flex-grow">
			<FormKit
				type="text"
				name="other_names"
				id="other_names"
				label="Other Names"
				placeholder="other names"
				validation="length:2,100"
			/>
		</div>
	</div>
	<FormKit
		id="maiden_name"
		name="maiden_name"
		type="text"
		label="Maiden name"
		placeholder="Maiden name"
		outer-class="md:flex-grow"
	/>
	<div class="md:flex md:gap-2 justify-between flex-wrap">
		<FormKit
			id="date_of_birth"
			type="date"
			name="date_of_birth"
			:min="format(subYears(new Date(), 150), 'yyyy-MM-dd')"
			:max="format(new Date(), 'yyyy-MM-dd')"
			label="Date of birth"
			:validation="
				'date_after:' +
				format(subYears(new Date(), 130), 'yyyy-MM-dd') +
				'|date_before:' +
				format(addDays(new Date(), 1), 'yyyy-MM-dd')
			"
			validation-visibility="submit"
			outer-class="md:flex-grow"
		/>
		<FormKit
			id="gender"
			name="gender"
			type="select"
			label="Gender"
			validation=""
			placeholder="Select one"
			:options="gender"
			outer-class="md:flex-grow"
		/>
		<div class="flex gap-3">
			<FormKit
				id="place_of_birth"
				name="place_of_birth"
				type="text"
				label="Place of Birth"
				placeholder="Place of Birth"
				outer-class="md:flex-grow"
			/>
			<FormKit
				id="country_of_birth"
				name="country_of_birth"
				type="select"
				label="Country of Birth"
				placeholder="Select Country of Birth"
				:options="country"
				outer-class="md:flex-grow"
			/>
		</div>
		<FormKit
			id="nationality"
			name="nationality"
			type="select"
			label="Nationality"
			placeholder="Select nationality"
			:options="nationality"
			outer-class="md:flex-grow"
		/>
		<FormKit
			id="ghana_card"
			name="ghana_card"
			type="text"
			label="Ghana Card Number"
			placeholder="GHA-##########-#"
			outer-class=" md:flex-grow"
		/>
		<FormKit
			id="marital_status"
			type="select"
			label="Marital Status"
			name="marital_status"
			placeholder="Select one"
			validation=""
			:options="maritalStatus"
			outer-class="flex-start md:flex-grow"
		/>
		<FormKit
			id="religion"
			type="text"
			name="religion"
			label="Religion"
			placeholder="religion"
			validation="length:0,40"
			outer-class="md:flex-grow "
		/>
		<FormKit
			id="ethnicity"
			type="text"
			name="ethnicity"
			label="Ethnicity"
			placeholder="Ethnicity"
			validation="length:2,40"
			outer-class="md:flex-grow"
		/>
	</div>
	<FormKit
		id="about"
		type="textarea"
		name="about"
		label="About"
		placeholder="about"
		validation="length:2,200"
	/>
</template>
