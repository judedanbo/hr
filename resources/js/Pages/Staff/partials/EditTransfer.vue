<script setup>
import { Inertia } from "@inertiajs/inertia";
import { onMounted, ref } from "vue";
const emit = defineEmits(["formSubmitted"]);

const props = defineProps({
	institution: { type: Number, required: true },
	transfer: { type: Object, required: true },
});

let units = ref([]);

onMounted(async () => {
	const response = await axios.get(
		route("institution.unit-list", { institution: props.institution }),
	);
	units.value = response.data;
});

const submitHandler = (data) => {
	Inertia.patch(
		route("staff.transfer.update", {
			staff: props.transfer.staff_id,
			unit: props.transfer.unit_id,
		}),
		data,
		{
			preserveScroll: true,
			onSuccess: () => {
				emit("formSubmitted");
			},
			onError: (errors) => {},
		},
	);
};
</script>

<template>
	<main class="px-8 py-8 bg-gray-100 dark:bg-gray-700">
		<h1 class="text-2xl pb-4 dark:text-gray-100">Edit Staff Transfer</h1>

		<FormKit
			type="form"
			submit-label="Save"
			:value="{
				unit_id: transfer.unit_id,
				staff_id: transfer.staff_id,
				start_date: transfer.start_date_unix,
				end_date: transfer.end_date_unix,
				remarks: transfer.remarks,
			}"
			@submit="submitHandler"
		>
			<FormKit id="staff_id" type="hidden" name="staff_id" />
			<!-- {{ transfer }} -->
			<FormKit
				id="unit_id"
				type="select"
				name="unit_id"
				label="New Location"
				placeholder="Select new location"
				:options="units"
				error-visibility="submit"
			/>
			<div class="sm:flex gap-4">
				<FormKit
					id="start_date"
					type="date"
					name="start_date"
					label="Start date"
					validation-visibility="submit"
				/>
				<FormKit id="end_date" type="date" name="end_date" label="End date" />
			</div>
			<FormKit
				id="remarks"
				type="text"
				name="remarks"
				label="Remarks"
				validation="string|length:2,120"
				validation-visibility="submit"
			/>
		</FormKit>
		<div v-if="transfer.old_data" class="dark:text-gray-50 w-5/6">
			<p class="">Old Data</p>
			<pre class="text-sm" v-html="transfer.old_data.replace('\\n', '<')"></pre>
		</div>
	</main>
</template>
