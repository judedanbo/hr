<script setup>
import SubMenu from "@/Components/SubMenu.vue";
import { usePage } from "@inertiajs/inertia-vue3";
import { computed } from "vue";
const emit = defineEmits(["editTransfer", "deleteTransfer", "approveTransfer"]);

const page = usePage();
const permissions = computed(() => page.props.value.auth.permissions);
const subMenuClicked = (action, model) => {
	if (action == "Edit") {
		emit("editTransfer", model);
	}
	if (action == "Delete") {
		emit("deleteTransfer", model);
	}
	if (action == "Approve") {
		// let approve = axios
		// 	.patch(
		// 		route("staff.transfer.approve", {
		// 			staff: model.staff_id,
		// 			unit: model.unit_id,
		// 		}),
		// 	)
		// 	.then(function (response) {
		// 		if (response.data) {
		// 			window.location.reload();
		// 		}
		// 	});
		emit("approveTransfer", model);
	}
};
let props = defineProps({
	editTransfer: { type: Boolean, default: true },
	transfers: { type: [Array, null], default: () => null },
});
</script>
<template>
	<!-- Transfer List -->
	<main>
		<div class="-mx-4 mt-8 flow-root sm:mx-0 w-full px-4">
			<table
				v-if="transfers !== null && transfers.length > 0"
				class="min-w-full"
			>
				<colgroup></colgroup>
				<thead
					class="border-b border-gray-300 dark:border-gray-200/50 text-gray-900 dark:text-gray-50"
				>
					<tr>
						<th
							scope="col"
							class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 dark:text-gray-50 sm:pl-0"
						>
							Post
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
						<th
							scope="col"
							class="hidden px-3 py-3.5 text-right text-sm font-semibold text-gray-900 dark:text-gray-50 sm:table-cell"
						>
							Status
						</th>
						<th v-if="editTransfer"></th>
					</tr>
				</thead>
				<tbody>
					<tr
						v-for="transfer in transfers"
						:key="transfer.id"
						class="border-b border-gray-200 dark:border-gray-400/30"
					>
						<td class="max-w-0 py-2 pl-1 pr-3 text-sm sm:pl-0 w-2/4">
							<div class="font-medium text-gray-900 dark:text-gray-50">
								{{ transfer.unit_name }}
							</div>
							<div class="mt-1 truncate text-gray-500 dark:text-gray-100">
								{{ transfer.remarks }}
							</div>
						</td>
						<td
							class="hidden p-1 text-right text-xs text-gray-500 dark:text-gray-100 sm:table-cell w-1/4"
						>
							{{ transfer.start_date }}
						</td>
						<td
							class="hidden p-1 text-right text-xs text-gray-500 dark:text-gray-100 sm:table-cell w-1/4"
						>
							{{ transfer.end_date }}
						</td>
						<td
							:class="transfer.status_color"
							class="hidden p-1 text-right text-xs sm:table-cell w-1/4"
						>
							{{ transfer.status }}
						</td>
						<td v-if="editTransfer" class="flex justify-end">
							<SubMenu
								v-if="
									permissions.includes('update staff') ||
									permissions.includes('delete staff')
								"
								:can-edit="permissions.includes('update staff')"
								:can-delete="permissions.includes('delete staff')"
								:can-approve="permissions.includes('approve staff transfer')"
								:items="['Approve', 'Edit', 'Delete']"
								@item-clicked="(action) => subMenuClicked(action, transfer)"
							/>
						</td>
					</tr>
				</tbody>
			</table>
			<div
				v-else
				class="px-4 py-6 text-sm font-bold text-gray-400 dark:text-gray-100 tracking-wider text-center"
			>
				No transfers found.
			</div>
		</div>
		<!-- <Alert alert=""/> -->
	</main>
</template>
