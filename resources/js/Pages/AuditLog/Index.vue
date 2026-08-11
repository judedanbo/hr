<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, router, usePage } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import Pagination from "@/Components/Pagination.vue";
import PageShell from "@/Components/UI/PageShell.vue";
import PageHeader from "@/Components/UI/PageHeader.vue";
import ListToolbar from "@/Components/UI/ListToolbar.vue";
import Modal from "@/Components/NewModal.vue";
import ActivityList from "./partials/ActivityList.vue";
import { useNavigation } from "@/Composables/navigation";
import { useSearch } from "@/Composables/search";
import { useToggle } from "@vueuse/core";
import Delete from "@/Components/Delete.vue";

const navigation = computed(() => useNavigation(props.activities));

const props = defineProps({
	activities: { type: Object, required: true },
	filters: { type: Object, default: () => ({}) },
	filterOptions: { type: Object, default: () => ({}) },
});

const page = usePage();
const permissions = computed(() => page.props?.auth.permissions);

// Filter state
const selectedLogName = ref(props.filters.log_name || "");
const selectedEvent = ref(props.filters.event || "");
const dateFrom = ref(props.filters.date_from || "");
const dateTo = ref(props.filters.date_to || "");

// Delete modal state
const openDeleteModal = ref(false);
const toggleDeleteModal = useToggle(openDeleteModal);
const selectedActivity = ref(null);

const searchActivity = (value) => {
	useSearch(value, route("audit-log.index"));
};

const applyFilters = () => {
	router.get(
		route("audit-log.index"),
		{
			log_name: selectedLogName.value,
			event: selectedEvent.value,
			date_from: dateFrom.value,
			date_to: dateTo.value,
			search: props.filters.search,
		},
		{ preserveState: true, replace: true },
	);
};

const clearFilters = () => {
	selectedLogName.value = "";
	selectedEvent.value = "";
	dateFrom.value = "";
	dateTo.value = "";
	router.get(
		route("audit-log.index"),
		{},
		{ preserveState: true, replace: true },
	);
};

const viewActivity = (activity) => {
	router.visit(route("audit-log.show", { auditLog: activity.id }));
};

const deleteActivity = (activity) => {
	selectedActivity.value = activity;
	toggleDeleteModal();
};

const deleteConfirmed = () => {
	router.delete(
		route("audit-log.delete", { auditLog: selectedActivity.value.id }),
		{
			onSuccess: () => {
				toggleDeleteModal();
			},
		},
	);
};

const breadcrumbLinks = [{ name: "Audit Log", url: null }];
</script>

<template>
	<MainLayout>
		<Head title="Audit Log" />
		<PageShell v-if="permissions?.includes('view user activity')">
			<PageHeader
				title="Audit Log"
				description="Every recorded action, who performed it and when."
				:breadcrumbs="breadcrumbLinks"
				:count="activities.total"
			/>

			<ListToolbar
				title="Audit Log"
				:search="filters.search"
				:show-action="false"
				@search-entered="(value) => searchActivity(value)"
			/>

			<!-- Filters -->
			<div
				class="grid grid-cols-1 gap-4 rounded-2xl border border-green-200/60 bg-white p-5 shadow-sm sm:grid-cols-2 md:grid-cols-4 dark:border-gray-700 dark:bg-gray-800"
			>
				<div>
					<label
						class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
					>
						Log Name
					</label>
					<select
						v-model="selectedLogName"
						class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-green-500 focus:ring-green-500 dark:focus:border-green-400 dark:focus:ring-green-400 sm:text-sm"
						@change="applyFilters"
					>
						<option value="">All</option>
						<option
							v-for="logName in filterOptions.logNames"
							:key="logName"
							:value="logName"
						>
							{{ logName }}
						</option>
					</select>
				</div>
				<div>
					<label
						class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
					>
						Event
					</label>
					<select
						v-model="selectedEvent"
						class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-green-500 focus:ring-green-500 dark:focus:border-green-400 dark:focus:ring-green-400 sm:text-sm"
						@change="applyFilters"
					>
						<option value="">All</option>
						<option
							v-for="event in filterOptions.events"
							:key="event"
							:value="event"
						>
							{{ event }}
						</option>
					</select>
				</div>
				<div>
					<label
						class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
					>
						Date From
					</label>
					<input
						v-model="dateFrom"
						type="date"
						class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-green-500 focus:ring-green-500 dark:focus:border-green-400 dark:focus:ring-green-400 sm:text-sm"
						@change="applyFilters"
					/>
				</div>
				<div>
					<label
						class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
					>
						Date To
					</label>
					<input
						v-model="dateTo"
						type="date"
						class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-green-500 focus:ring-green-500 dark:focus:border-green-400 dark:focus:ring-green-400 sm:text-sm"
						@change="applyFilters"
					/>
				</div>
			</div>

			<!-- Clear Filters Button -->
			<div
				v-if="selectedLogName || selectedEvent || dateFrom || dateTo"
				class="mb-4"
			>
				<button
					class="text-sm text-green-700 hover:text-green-800 dark:text-green-300 dark:hover:text-green-200"
					@click="clearFilters"
				>
					Clear all filters
				</button>
			</div>

			<ActivityList
				:activities="activities.data"
				@view-activity="viewActivity"
				@delete-activity="deleteActivity"
			>
				<template #pagination>
					<Pagination :navigation="navigation" />
				</template>
			</ActivityList>
		</PageShell>
		<PageShell v-else>
			<div
				class="rounded-2xl border border-green-200/60 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800"
			>
				<p class="text-sm text-gray-600 dark:text-gray-400">
					You do not have permission to view audit logs.
				</p>
			</div>
		</PageShell>

		<Delete
			:show="openDeleteModal"
			:model="selectedActivity"
			model-name="activity log entry"
			@close="toggleDeleteModal"
			@delete-confirmed="deleteConfirmed"
		/>
	</MainLayout>
</template>
