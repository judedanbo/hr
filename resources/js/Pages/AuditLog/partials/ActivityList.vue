<script setup>
import { computed } from "vue";
import { usePage, Link } from "@inertiajs/vue3";
import MainTable from "@/Components/Table/MainTable.vue";
import TableHead from "@/Components/Table/TableHead.vue";
import TableBody from "@/Components/Table/TableBody.vue";
import TableRow from "@/Components/Table/TableRow.vue";
import TableData from "@/Components/Table/TableData.vue";
import RowHeader from "@/Components/Table/RowHeader.vue";
import NoItem from "@/Components/NoItem.vue";
import SubMenu from "@/Components/SubMenu.vue";

const emit = defineEmits(["viewActivity", "deleteActivity"]);

const props = defineProps({
	activities: { type: Array, required: true },
});

const page = usePage();
const permissions = computed(() => page.props?.auth.permissions);

const subMenuClicked = (action, activity) => {
	if (action === "View") emit("viewActivity", activity);
	if (action === "Delete") emit("deleteActivity", activity);
};

const getEventBadgeClass = (event) => {
	switch (event) {
		case "created":
			return "bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300";
		case "updated":
			return "bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300";
		case "deleted":
			return "bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300";
		case "authorization_failed":
			return "bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300";
		case "authorization_success":
		case "success":
			return "bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-300";
		default:
			return "bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300";
	}
};
</script>

<template>
	<section class="flex flex-col mt-6 -my-2 overflow-x-auto">
		<div class="inline-block min-w-full py-2 align-middle">
			<MainTable v-if="activities.length > 0">
				<TableHead>
					<RowHeader>Date</RowHeader>
					<RowHeader>Event</RowHeader>
					<RowHeader>Description</RowHeader>
					<RowHeader>User</RowHeader>
					<RowHeader>Subject</RowHeader>
					<RowHeader>Actions</RowHeader>
				</TableHead>
				<TableBody>
					<template v-for="activity in activities" :key="activity.id">
						<TableRow>
							<TableData>
								<span class="text-sm text-gray-600 dark:text-gray-400">
									{{ activity.created_at }}
								</span>
							</TableData>
							<TableData>
								<span
									:class="[
										'px-2 py-1 text-xs font-medium rounded-full',
										getEventBadgeClass(activity.event),
									]"
								>
									{{ activity.event || "N/A" }}
								</span>
							</TableData>
							<TableData>
								<span class="text-sm dark:text-gray-300">
									{{ activity.description }}
								</span>
							</TableData>
							<TableData>
								<span class="text-sm dark:text-gray-300">
									{{ activity.causer_name }}
								</span>
							</TableData>
							<TableData>
								<span
									v-if="activity.subject_type"
									class="text-sm dark:text-gray-300"
								>
									{{ activity.subject_type }}
									<span class="text-gray-500">#{{ activity.subject_id }}</span>
								</span>
								<span v-else class="text-sm text-gray-400">-</span>
							</TableData>
							<TableData>
								<SubMenu
									:items="['View', 'Delete']"
									:can-edit="permissions?.includes('view user activity')"
									:can-delete="permissions?.includes('view user activity')"
									@item-clicked="(action) => subMenuClicked(action, activity)"
								/>
							</TableData>
						</TableRow>
					</template>
				</TableBody>
			</MainTable>
			<NoItem v-else name="Activity Logs" />
			<slot name="pagination" />
		</div>
	</section>
</template>
