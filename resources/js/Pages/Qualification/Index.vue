<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import Pagination from "@/Components/Pagination.vue";
import QualificationList from "./QualificationList.vue";
import { ref, watch } from "vue";
import { Inertia } from "@inertiajs/inertia";
let props = defineProps({
	qualifications: {
		type: Object,
		default: null,
	},
	filters: {
		type: Object,
		default: null,
	},
});

const searchQualification = (value) => {
	Inertia.get(
		route("qualification.index"),
		{ search: value },
		{ preserveState: true, replace: true, preserveScroll: true },
	);
};
</script>

<template>
	<MainLayout>
		Qualifications
		<QualificationList
			:qualifications="qualifications.data"
			@update:model-value="searchQualification"
		/>
		<Pagination :records="qualifications" />
	</MainLayout>
</template>
