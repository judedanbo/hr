<script setup>
import { ref } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import { useToggle } from "@vueuse/core";
import axios from "axios";

import NewLayout from "@/Layouts/NewAuthenticated.vue";
import Modal from "@/Components/NewModal.vue";
import StaffListModal from "@/Components/StaffListModal.vue";

// Section components
import OverviewStatsSection from "./partials/OverviewStatsSection.vue";
import StaffAnalyticsSection from "./partials/StaffAnalyticsSection.vue";
import RecruitmentTrendsSection from "./partials/RecruitmentTrendsSection.vue";
import ActionItemsSection from "./partials/ActionItemsSection.vue";
import OrganizationalViewSection from "./partials/OrganizationalViewSection.vue";
import RankDistributionSection from "./partials/RankDistributionSection.vue";

// Unit management components (preserved from original)
import AddForm from "./partials/AddForm.vue";
import EditForm from "./partials/EditForm.vue";
import DeleteUnit from "../Unit/Delete.vue";

import { PlusIcon, Cog6ToothIcon } from "@heroicons/vue/24/outline";

const props = defineProps({
	institution: Object,
	overview: Object,
	trends: Object,
	analytics: Object,
	action_items: Array,
	departments: Array,
	filters: Object,
	can: Object,
});

// Unit management state (preserved from original)
const open = ref(false);
const openEditForm = ref(false);
const openDeleteModal = ref(false);
const toggle = useToggle(open);
const toggleEditForm = useToggle(openEditForm);
const toggleDeleteModal = useToggle(openDeleteModal);
const selectedUnit = ref(null);

// Staff list modal state
const showStaffModal = ref(false);
const modalTitle = ref("");
const modalStaff = ref([]);
const modalLoading = ref(false);

// Unit management functions (preserved from original)
const newDepartment = () => {
	toggle();
};

const editDepartment = (id) => {
	selectedUnit.value = props.departments.find(
		(department) => department.id === id,
	);
	toggleEditForm();
};

const deleteUnit = (id) => {
	selectedUnit.value = props.departments.find(
		(department) => department.id === id,
	);
	toggleDeleteModal();
};

// Staff modal functions
async function openStaffModal(data) {
	showStaffModal.value = true;
	modalTitle.value = data.title;
	modalLoading.value = true;
	modalStaff.value = [];

	try {
		const params = new URLSearchParams({
			filter: data.filter,
			...data.params,
		});

		const response = await axios.get(
			route("institution.staff-filter", props.institution.id) +
				"?" +
				params.toString(),
		);

		modalStaff.value = response.data.staff;
	} catch (error) {
		console.error("Error fetching staff:", error);
		modalStaff.value = [];
	} finally {
		modalLoading.value = false;
	}
}

function closeStaffModal() {
	showStaffModal.value = false;
	modalStaff.value = [];
}
</script>

<template>
	<Head :title="institution?.name + ' Dashboard'" />

	<NewLayout>
		<main class="w-full px-4 sm:px-6 lg:px-8 py-6">
			<!-- Header -->
			<div
				class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8"
			>
				<div>
					<h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
						{{ institution?.name }}
					</h1>
					<p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
						Institution Dashboard
						<span v-if="institution?.abbreviation">
							({{ institution.abbreviation }})
						</span>
					</p>
				</div>
				<div class="flex items-center gap-3">
					<button
						v-if="can?.manage_units"
						type="button"
						class="inline-flex items-center gap-x-1.5 rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600 dark:bg-green-700 dark:hover:bg-green-600"
						@click="newDepartment"
					>
						<PlusIcon class="-ml-0.5 h-5 w-5" aria-hidden="true" />
						Add Unit
					</button>
				</div>
			</div>

			<!-- Dashboard Sections -->
			<div class="space-y-8">
				<!-- Overview Stats -->
				<OverviewStatsSection
					:overview="overview"
					@stat-click="openStaffModal"
				/>

				<!-- Staff Analytics Charts -->
				<StaffAnalyticsSection
					:analytics="analytics"
					@chart-click="openStaffModal"
				/>

				<!-- Rank Distribution -->
				<RankDistributionSection
					:distribution="analytics?.rank_distribution || []"
					@chart-click="openStaffModal"
				/>

				<!-- Recruitment Trends -->
				<RecruitmentTrendsSection :trends="trends" />

				<!-- Action Items -->
				<ActionItemsSection
					v-if="can?.view_action_items"
					:items="action_items"
					@item-click="openStaffModal"
				/>

				<!-- Organizational View -->
				<OrganizationalViewSection
					:departments="departments"
					:institution-id="institution?.id"
					@department-click="openStaffModal"
				/>
			</div>
		</main>

		<!-- Staff List Modal (for drill-down) -->
		<StaffListModal
			:show="showStaffModal"
			:title="modalTitle"
			:staff="modalStaff"
			:loading="modalLoading"
			@close="closeStaffModal"
		/>

		<!-- Add Unit Modal -->
		<Modal :show="open" @close="toggle()">
			<AddForm
				:institution-name="institution?.name"
				:institution-id="institution?.id"
			/>
		</Modal>

		<!-- Edit Unit Modal -->
		<Modal :show="openEditForm" @close="toggleEditForm()">
			<EditForm
				:institution-name="institution?.name"
				:institution-id="institution?.id"
				:unit="selectedUnit"
				@formSubmitted="toggleEditForm()"
			/>
		</Modal>

		<!-- Delete Unit Modal -->
		<Modal :show="openDeleteModal" @close="toggleDeleteModal()">
			<DeleteUnit
				:selected-model="selectedUnit"
				@unit-deleted="toggleDeleteModal()"
			/>
		</Modal>
	</NewLayout>
</template>
