<script setup>
import SubMenu from "@/Components/SubMenu.vue";
import { usePage } from "@inertiajs/inertia-vue3";
import { computed } from "vue";

const page = usePage();
const permissions = computed(() => page.props.value.auth.permissions);

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
	<div class="-mx-4 flow-root sm:mx-0 w-full px-4 overflow-y-auto">
		<table v-if="statuses.length > 0" class="min-w-full">
			<colgroup></colgroup>
			<thead
				class="border-b border-gray-300 text-gray-900 dark:border-gray-200/30 dark:text-gray-50"
			>
				<tr>
					<th
						scope="col"
						class="py-2 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 dark:text-gray-50 sm:pl-0"
					>
						Status
					</th>

					<th><div class="sr-only">Actions</div></th>
				</tr>
			</thead>
			<tbody>
				<tr v-for="status in statuses" :key="status.id" class="">
					<td class="max-w-0 py-2 pl-1 pr-3 text-sm sm:pl-0">
						<div class="font-medium text-gray-900 dark:text-gray-50">
							{{ status.status_display }}
						</div>
						<div class="mt-1 truncate text-gray-500 dark:text-gray-100 text-xs">
							{{ status.start_date_display }}
							{{
								status.end_date_display?.length > 0
									? " - " + status.end_date_display
									: " to date"
							}}
						</div>
					</td>
					<td class="flex justify-end">
						<SubMenu
							v-if="
								permissions.includes('update staff') ||
								permissions.includes('delete staff')
							"
							:can-edit="permissions.includes('update staff')"
							:can-delete="permissions.includes('delete staff')"
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
