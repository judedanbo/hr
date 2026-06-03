<script setup>
import { router } from "@inertiajs/vue3";
import { computed } from "vue";

const emit = defineEmits(["formSubmitted"]);
const props = defineProps({
	leaveType: { type: Object, required: true },
	genders: { type: Array, default: () => [] },
});

const genderOptions = computed(() => [
	{ value: "", label: "Any" },
	...props.genders,
]);

const initial = computed(() => ({
	name: props.leaveType.name,
	code: props.leaveType.code,
	gender_restriction: props.leaveType.gender_restriction ?? "",
	min_notice_days: props.leaveType.min_notice_days,
	max_consecutive_days: props.leaveType.max_consecutive_days,
	max_concurrent_per_unit: props.leaveType.max_concurrent_per_unit,
	color: props.leaveType.color,
	requires_evidence: props.leaveType.requires_evidence,
	counts_weekends: props.leaveType.counts_weekends,
	counts_holidays: props.leaveType.counts_holidays,
	is_active: props.leaveType.is_active,
}));

const submitHandler = (data, node) => {
	const payload = {
		...data,
		gender_restriction: data.gender_restriction || null,
	};
	router.patch(
		route("leave-type.update", { leaveType: props.leaveType.id }),
		payload,
		{
			preserveScroll: true,
			onSuccess: () => emit("formSubmitted"),
			onError: (errors) => node.setErrors([], errors),
		},
	);
};
</script>

<template>
	<main class="px-8 py-8 bg-gray-100 dark:bg-gray-700">
		<h1 class="text-2xl pb-4 dark:text-gray-100">Edit leave type</h1>
		<FormKit
			type="form"
			submit-label="Update"
			:value="initial"
			@submit="submitHandler"
		>
			<FormKit
				type="text"
				name="name"
				label="Name"
				validation="required|string"
			/>
			<FormKit
				type="text"
				name="code"
				label="Code"
				validation="required|string"
			/>
			<FormKit
				type="select"
				name="gender_restriction"
				label="Gender restriction"
				:options="genderOptions"
			/>
			<FormKit
				type="number"
				name="min_notice_days"
				label="Minimum notice (days)"
				validation="required|number|min:0"
			/>
			<FormKit
				type="number"
				name="max_consecutive_days"
				label="Max consecutive days (optional)"
				validation="number|min:1"
			/>
			<FormKit
				type="number"
				name="max_concurrent_per_unit"
				label="Max concurrent per unit (optional)"
				validation="number|min:1"
			/>
			<FormKit type="color" name="color" label="Colour" />
			<FormKit
				type="checkbox"
				name="requires_evidence"
				label="Evidence required"
			/>
			<FormKit type="checkbox" name="counts_weekends" label="Count weekends" />
			<FormKit
				type="checkbox"
				name="counts_holidays"
				label="Count public holidays"
			/>
			<FormKit type="checkbox" name="is_active" label="Active" />
		</FormKit>
	</main>
</template>
