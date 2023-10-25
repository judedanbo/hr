<script setup>
import { Inertia } from "@inertiajs/inertia";
import { onMounted, ref } from "vue";
const emit = defineEmits(["formSubmitted"]);

const props = defineProps({
	institution: Number,
	transfer: Object,
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
			onError: (errors) => {
				console.log(errors);
			},
		},
	);
};
</script>

<template>
	<main class="px-8 py-8 bg-gray-100 dark:bg-gray-700">
		<h1 class="text-2xl pb-4 dark:text-gray-100">Edit Staff Transfer</h1>

		<FormKit
			@submit="submitHandler"
			type="form"
			submit-label="Save"
			:value="{
				unit_id: transfer.unit_id,
				staff_id: transfer.staff_id,
				start_date: transfer.start_date_unix,
				end_date: transfer.end_date_unix,
				remarks: transfer.remarks,
			}"
		>
			<FormKit type="hidden" id="staff_id" name="staff_id" />
			<!-- {{ transfer }} -->
			<FormKit
				type="select"
				name="unit_id"
				id="unit_id"
				label="New Location"
				placeholder="Select new location"
				:options="units"
				error-visibility="submit"
			/>
			<div class="sm:flex gap-4">
				<FormKit
					type="date"
					name="start_date"
					id="start_date"
					label="Start date"
					validation-visibility="submit"
				/>
				<FormKit type="date" name="end_date" id="end_date" label="End date" />
			</div>
			<FormKit
				type="text"
				name="remarks"
				id="remarks"
				label="Remarks"
				validation="string|length:2,120"
				validation-visibility="submit"
			/>
		</FormKit>
		<div v-if="transfer.old_data" class="dark:text-gray-50">
			<p class="text-lg">Old Data</p>
			<pre class="text-sm">{{ transfer.old_data }}</pre>
		</div>
	</main>
</template>

<style scoped>
.formkit-outer {
	@apply w-full;
}

.formkit-submit {
	@apply justify-self-end;
}

.formkit-actions {
	@apply flex justify-end;
}
</style>
