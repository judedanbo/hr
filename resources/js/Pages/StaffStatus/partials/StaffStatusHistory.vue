<script setup>
import SubMenu from "@/Components/SubMenu.vue";

const emit = defineEmits(["editStaffStatus", "deleteStaffStatus"]);
defineProps({
	statuses: Array,
});
const subMenuClicked = (action, model) => {
	if (action == "Edit") {
		emit("editStaffStatus", model);
	}
	if (action == "Delete") {
		emit("deleteStaffStatus", model);
	}
};
</script>
<template>
	<div class="-mx-4 flow-root sm:mx-0 w-full p-4 overflow-y-auto">
		<table v-if="statuses.length > 0" class="min-w-full">
			<colgroup></colgroup>
			<thead
				class="border-b border-gray-300 text-gray-900 dark:border-gray-200/30 dark:text-gray-50"
			>
				<tr>
					<th
						scope="col"
						class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 dark:text-gray-50 sm:pl-0"
					>
						Status
					</th>
					<th
						scope="col"
						class="hidden px-3 py-3.5 text-right text-sm font-semibold text-gray-900 dark:text-gray-50 sm:table-cell"
					>
						Start
					</th>
					<th
						scope="col"
						class="hidden px-3 py-3.5 text-right text-sm font-semibold text-gray-900 dark:text-gray-50 sm:table-cell"
					>
						End
					</th>
					<th><div class="sr-only">Actions</div></th>
				</tr>
			</thead>
			<tbody>
				<tr
					v-for="status in statuses"
					:key="status.id"
					class="border-b border-gray-200 dark:border-gray-400/30"
				>
					<td class="max-w-0 py-2 pl-1 pr-3 text-sm sm:pl-0">
						<div class="font-medium text-gray-900 dark:text-gray-50">
							{{ status.status_display }}
						</div>
					</td>
					<td
						class="hidden px-1 py-5 text-right text-sm text-gray-500 dark:text-gray-100 sm:table-cell"
					>
						{{ status.start_date_display }}
					</td>
					<td
						class="hidden px-1 py-5 text-right text-sm text-gray-500 dark:text-gray-100 sm:table-cell"
					>
						{{ status.end_date_display }}
					</td>
					<td>
						<SubMenu
							@itemClicked="(action) => subMenuClicked(action, status)"
							:items="['Edit', 'Delete']"
						/>
					</td>
				</tr>
			</tbody>
		</table>
		<div
			v-else
			class="px-4 py-6 text-sm font-bold text-gray-400 dark:text-gray-100 tracking-wider text-center"
		>
			No status found.
		</div>
	</div>
</template>
