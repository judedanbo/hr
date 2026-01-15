<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import Pagination from "@/Components/Pagination.vue";
import QualificationList from "./QualificationList.vue";
import { ref, watch, computed } from "vue";
import { router } from "@inertiajs/vue3";
import { useNavigation } from "@/Composables/navigation";
let props = defineProps({
	qualifications: {
		type: Object,
		default: null,
	},
	filters: {
		type: Object,
		default: null,
	},
	can: {
		type: Object,
		default: () => ({}),
	},
});
const navigation = computed(() => useNavigation(props.qualifications));
const searchQualification = (value) => {
	router.get(
		route("qualification.index"),
		{ search: value },
		{ preserveState: true, replace: true, preserveScroll: true },
	);
};

const approveQualification = (qualification) => {
	router.patch(
		route("qualification.approve", { qualification: qualification.id }),
		{},
		{
			preserveScroll: true,
		},
	);
};
</script>

<template>
	<MainLayout>
		Qualifications
		<QualificationList
			:qualifications="qualifications.data"
			:can-approve="can.approve"
			@update:model-value="searchQualification"
			@approve-qualification="approveQualification"
		/>
		<Pagination :navigation="navigation" />
	</MainLayout>
</template>
