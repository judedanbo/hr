<script setup>
import { Inertia } from "@inertiajs/inertia";
import { onMounted, ref } from "vue";
import StaffTypeForm from "@/Pages/StaffType/partials/StaffTypeForm.vue";

const emit = defineEmits(["formSubmitted"]);

const props = defineProps({
	staff: Object,
	institution: Number,
	staffType: Object,
});

const submitHandler = (data, node) => {
	Inertia.patch(
		route("staff-type.update", {
			staff: data.staff_id,
			staffType: props.staffType.id,
		}),
		data,
		{
			preserveScroll: true,
			onSuccess: () => {
				node.reset();
				emit("formSubmitted");
			},
			onError: (errors) => {
				node.setErrors([""], errors);
			},
		},
	);
};
</script>

<template>
	<main class="px-8 py-8 bg-gray-100 dark:bg-gray-700">
		<h1 class="text-2xl pb-4 dark:text-gray-100">Change Staff Type</h1>
		<FormKit
			@submit="submitHandler"
			type="form"
			submit-label="Save"
			:value="{
				staff_type: staffType.type,
				start_date: staffType.start_date,
				end_date: staffType.end_date,
			}"
		>
			<FormKit type="hidden" id="staff_id" name="staff_id" :value="staff.id" />
			<FormKit
				type="hidden"
				id="institution_id"
				name="institution_id"
				:value="institution"
			/>

			<StaffTypeForm :institution="institution" :hire_date="staff.hire_date" />
		</FormKit>
	</main>
</template>
