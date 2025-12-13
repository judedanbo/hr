<script setup>
import { ref, computed } from "vue";
import { Head, usePage } from "@inertiajs/vue3";
import { useToggle } from "@vueuse/core";
import { router } from "@inertiajs/vue3";

import MainLayout from "@/Layouts/NewAuthenticated.vue";
import Modal from "@/Components/NewModal.vue";

// New section components
import UnitHeroSection from "./partials/UnitHeroSection.vue";
import UnitStatsSection from "./partials/UnitStatsSection.vue";
import SubUnitsCardGrid from "./partials/SubUnitsCardGrid.vue";
import RankDistributionSection from "./partials/RankDistributionSection.vue";
import StaffDirectorySection from "./partials/StaffDirectorySection.vue";

// Modal components
import EditUnit from "./partials/Edit.vue";
import AddSubUnit from "./partials/AddSubUnit.vue";
import DeleteUnit from "./Delete.vue";

const props = defineProps({
	unit: Object,
	filters: Object,
	rank_distribution: Array,
});

const page = usePage();
const permissions = computed(() => page.props?.auth.permissions);

// Modal states
const openEditModal = ref(false);
const toggleEditForm = useToggle(openEditModal);
const openAddSubUnitModal = ref(false);
const toggleAddUnitForm = useToggle(openAddSubUnitModal);
const openDeleteModal = ref(false);
const toggleDeleteModal = useToggle(openDeleteModal);

// Handle unit deleted - redirect to parent or index
const handleUnitDeleted = () => {
	toggleDeleteModal();
	if (props.unit?.parent?.id) {
		router.visit(route("unit.show", { unit: props.unit.parent.id }));
	} else {
		router.visit(
			route("unit.index", { institution: props.unit.institution?.id }),
		);
	}
};

// Handle search from staff directory
const handleSearch = (query) => {
	router.get(
		route("unit.show", { unit: props.unit.id }),
		{ search: query },
		{ preserveState: true, replace: true, preserveScroll: true },
	);
};
</script>

<template>
	<Head :title="props.unit?.name" />

	<MainLayout>
		<main class="w-full px-4 sm:px-6 lg:px-8 py-6">
			<!-- Hero Section with Header -->
			<UnitHeroSection
				:unit="props.unit"
				:permissions="permissions"
				@edit="toggleEditForm()"
				@add-sub-unit="toggleAddUnitForm()"
				@delete="toggleDeleteModal()"
			/>

			<!-- Dashboard Sections -->
			<div class="space-y-8">
				<!-- Overview Stats -->
				<UnitStatsSection :unit="props.unit" />

				<!-- Sub-Units Card Grid -->
				<SubUnitsCardGrid
					v-if="props.unit?.subs?.length > 0"
					:subs="props.unit.subs"
					:parent-name="props.unit.name"
					:can-download="permissions?.includes('download active staff data')"
				/>

				<!-- Rank Distribution -->
				<RankDistributionSection
					v-if="props.rank_distribution?.length > 0"
					:distribution="props.rank_distribution"
				/>

				<!-- Staff Directory -->
				<StaffDirectorySection
					:staff="props.unit?.staff || []"
					:subs="props.unit?.subs || []"
					:unit-id="props.unit?.id"
					:unit-name="props.unit?.name"
					:can-download="permissions?.includes('download active staff data')"
					@search="handleSearch"
				/>
			</div>
		</main>

		<!-- Edit Unit Modal -->
		<Modal :show="openEditModal" @close="toggleEditForm()">
			<EditUnit :unit="props.unit?.id" @form-submitted="toggleEditForm()" />
		</Modal>

		<!-- Add Sub-Unit Modal -->
		<Modal :show="openAddSubUnitModal" @close="toggleAddUnitForm()">
			<AddSubUnit
				:unit="props.unit?.id"
				@form-submitted="toggleAddUnitForm()"
			/>
		</Modal>

		<!-- Delete Unit Modal -->
		<Modal :show="openDeleteModal" @close="toggleDeleteModal()">
			<DeleteUnit
				:selected-model="props.unit"
				@cancel-delete="toggleDeleteModal()"
				@unit-deleted="handleUnitDeleted"
			/>
		</Modal>
	</MainLayout>
</template>
