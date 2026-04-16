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
import UnitOfficeSection from "./partials/UnitOfficeSection.vue";
import SubUnitsCardGrid from "./partials/SubUnitsCardGrid.vue";
import RankDistributionSection from "./partials/RankDistributionSection.vue";
import StaffDirectorySection from "./partials/StaffDirectorySection.vue";

// Modal components
import EditUnit from "./partials/Edit.vue";
import AddSubUnit from "./partials/AddSubUnit.vue";
import DeleteUnit from "./Delete.vue";
import ManageOfficeModal from "./partials/ManageOfficeModal.vue";
import RemoveOfficeModal from "./partials/RemoveOfficeModal.vue";

const props = defineProps({
	unit: Object,
	stats: Object,
	subs: Array,
	staff: Object,
	rank_distribution: Array,
	filter_options: Object,
	filters: Object,
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
const openManageOfficeModal = ref(false);
const toggleManageOfficeModal = useToggle(openManageOfficeModal);
const openRemoveOfficeModal = ref(false);
const toggleRemoveOfficeModal = useToggle(openRemoveOfficeModal);

// Check if user can edit unit
const canEditUnit = computed(() => permissions.value?.includes("edit unit"));

// Get current office (first item from the array since it's a BelongsToMany)
const currentOffice = computed(() => {
	const offices = props.unit?.current_office;
	return Array.isArray(offices) ? offices[0] : offices;
});

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
				<UnitStatsSection :stats="props.stats" />

				<!-- Office Location -->
				<UnitOfficeSection
					:office="currentOffice"
					:can-edit="canEditUnit"
					@manage="toggleManageOfficeModal()"
					@remove="toggleRemoveOfficeModal()"
				/>

				<!-- Sub-Units Card Grid -->
				<SubUnitsCardGrid
					v-if="props.subs?.length > 0"
					:subs="props.subs"
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
					:staff="props.staff"
					:filter-options="props.filter_options"
					:filters="props.filters"
					:unit-id="props.unit?.id"
					:unit-name="props.unit?.name"
					:can-download="permissions?.includes('download active staff data')"
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

		<!-- Manage Office Modal -->
		<Modal :show="openManageOfficeModal" @close="toggleManageOfficeModal()">
			<ManageOfficeModal
				:unit-id="props.unit?.id"
				:current-office="currentOffice"
				@form-submitted="toggleManageOfficeModal()"
			/>
		</Modal>

		<!-- Remove Office Modal -->
		<Modal :show="openRemoveOfficeModal" @close="toggleRemoveOfficeModal()">
			<RemoveOfficeModal
				:unit-id="props.unit?.id"
				:office-name="currentOffice?.name"
				@cancel="toggleRemoveOfficeModal()"
				@removed="toggleRemoveOfficeModal()"
			/>
		</Modal>
	</MainLayout>
</template>
