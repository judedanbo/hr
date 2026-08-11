<script setup>
import { computed } from "vue";
import { usePage, Link } from "@inertiajs/vue3";
import DataTable from "@/Components/UI/DataTable.vue";
import TableRow from "@/Components/TableRow.vue";
import TableData from "@/Components/TableData.vue";
import RowHeader from "@/Components/RowHeader.vue";
import Badge from "@/Components/UI/Badge.vue";
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

const eventVariant = (event) => {
	switch (event) {
		case "created":
		case "authorization_success":
		case "success":
			return "success";
		case "updated":
			return "info";
		case "deleted":
			return "danger";
		case "authorization_failed":
			return "warning";
		default:
			return "neutral";
	}
};
</script>

<template>
	<DataTable :has-items="activities.length > 0" name="Activity Logs">
		<template #head>
			<RowHeader>Date</RowHeader>
			<RowHeader>Event</RowHeader>
			<RowHeader>Description</RowHeader>
			<RowHeader>User</RowHeader>
			<RowHeader>Subject</RowHeader>
			<RowHeader align="right">Actions</RowHeader>
		</template>
		<template #body>
			<TableRow
				v-for="activity in activities"
				:key="activity.id"
				clickable
				@click="emit('viewActivity', activity)"
			>
				<TableData>{{ activity.created_at }}</TableData>
				<TableData>
					<Badge :variant="eventVariant(activity.event)">
						{{ activity.event || "N/A" }}
					</Badge>
				</TableData>
				<TableData :nowrap="false" primary>
					{{ activity.description }}
				</TableData>
				<TableData>{{ activity.causer_name }}</TableData>
				<TableData>
					<template v-if="activity.subject_type">
						{{ activity.subject_type }}
						<span class="text-gray-500 dark:text-gray-400"
							>#{{ activity.subject_id }}</span
						>
					</template>
					<span v-else class="text-gray-400 dark:text-gray-500">-</span>
				</TableData>
				<TableData align="right" @click.stop>
					<SubMenu
						:items="['View', 'Delete']"
						:can-edit="permissions?.includes('view user activity')"
						:can-delete="permissions?.includes('view user activity')"
						@item-clicked="(action) => subMenuClicked(action, activity)"
					/>
				</TableData>
			</TableRow>
		</template>
		<template #footer>
			<slot name="pagination" />
		</template>
	</DataTable>
</template>
