<script setup>
import { router } from "@inertiajs/vue3";
import { reactive, ref, computed, watch, onMounted } from "vue";
import axios from "axios";

const props = defineProps({
	mode: { type: String, default: "create" },
	request: { type: Object, default: null },
	leaveTypes: { type: Array, default: () => [] },
	planItems: { type: Array, default: () => [] },
});

const form = reactive({
	leave_plan_item_id: "",
	leave_type_id: props.request?.leave_type_id ?? "",
	start_date: props.request?.start_date ?? "",
	end_date: props.request?.end_date ?? "",
	reason: props.request?.reason ?? "",
	address_during_leave: props.request?.address_during_leave ?? "",
	contact_during_leave: props.request?.contact_during_leave ?? "",
	relieving_officer_id: props.request?.relieving_officer_id ?? "",
});

const fileModel = ref([]);
const previewDays = ref(props.request?.requested_days ?? null);
const relievingOfficers = ref([]);

const selectedType = computed(() =>
	props.leaveTypes.find((t) => t.value === Number(form.leave_type_id)),
);

onMounted(async () => {
	const { data } = await axios.get(route("leave-request.relieving-officers"));
	relievingOfficers.value = data;
});

const applyPlanItem = () => {
	const item = props.planItems.find(
		(p) => p.value === Number(form.leave_plan_item_id),
	);
	if (!item) return;
	form.leave_type_id = item.leave_type_id;
	form.start_date = item.start_date;
	form.end_date = item.end_date;
};

watch(
	() => [form.leave_type_id, form.start_date, form.end_date],
	async () => {
		if (!form.leave_type_id || !form.start_date || !form.end_date) {
			previewDays.value = null;
			return;
		}
		try {
			const { data } = await axios.get(route("leave-request.preview-days"), {
				params: {
					leave_type_id: form.leave_type_id,
					start_date: form.start_date,
					end_date: form.end_date,
				},
			});
			previewDays.value = data.days;
		} catch (e) {
			previewDays.value = null;
		}
	},
);

const submitHandler = (data, node) => {
	const payload = {
		...form,
		leave_plan_item_id: form.leave_plan_item_id || null,
		relieving_officer_id: form.relieving_officer_id || null,
		file_name: fileModel.value.map((f) => f.file),
	};

	if (props.mode === "edit") {
		payload._method = "patch";
		router.post(
			route("leave-request.update", { leaveRequest: props.request.id }),
			payload,
			{ forceFormData: true, onError: (errors) => node.setErrors([], errors) },
		);
		return;
	}

	router.post(route("leave-request.store"), payload, {
		forceFormData: true,
		onError: (errors) => node.setErrors([], errors),
	});
};
</script>

<template>
	<FormKit
		type="form"
		:submit-label="mode === 'edit' ? 'Update request' : 'Submit request'"
		@submit="submitHandler"
	>
		<FormKit
			v-if="mode === 'create' && planItems.length"
			v-model="form.leave_plan_item_id"
			type="select"
			label="Prefill from a planned leave (optional)"
			:options="[{ value: '', label: '— none —' }, ...planItems]"
			@input="applyPlanItem"
		/>
		<FormKit
			v-model="form.leave_type_id"
			type="select"
			name="leave_type_id"
			label="Leave type"
			placeholder="Select type"
			:options="leaveTypes"
			validation="required"
		/>
		<p
			v-if="selectedType && selectedType.remaining !== null"
			class="text-xs text-gray-500 dark:text-gray-300 pb-2"
		>
			Remaining balance: {{ selectedType.remaining }} day(s)
			<span v-if="selectedType.requires_evidence" class="text-red-600">
				· evidence required</span
			>
		</p>
		<FormKit
			v-model="form.start_date"
			type="date"
			name="start_date"
			label="Start date"
			validation="required|date"
		/>
		<FormKit
			v-model="form.end_date"
			type="date"
			name="end_date"
			label="End date"
			validation="required|date"
		/>
		<p
			v-if="previewDays !== null"
			class="text-sm text-gray-600 dark:text-gray-200 pb-2"
		>
			Countable leave days: <strong>{{ previewDays }}</strong>
		</p>
		<FormKit
			v-model="form.reason"
			type="textarea"
			name="reason"
			label="Reason (optional)"
		/>
		<FormKit
			v-model="form.address_during_leave"
			type="text"
			name="address_during_leave"
			label="Address during leave"
			validation="required"
		/>
		<FormKit
			v-model="form.contact_during_leave"
			type="text"
			name="contact_during_leave"
			label="Contact during leave"
			validation="required"
		/>
		<FormKit
			v-model="form.relieving_officer_id"
			type="select"
			name="relieving_officer_id"
			label="Relieving officer (optional)"
			:options="[{ value: '', label: '— none —' }, ...relievingOfficers]"
		/>
		<FormKit
			v-model="fileModel"
			type="file"
			name="file_name"
			label="Evidence (PDF/image)"
			accept=".pdf,.jpg,.jpeg,.png"
			multiple="true"
		/>
	</FormKit>
</template>
