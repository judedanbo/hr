<script setup>
import {
	EllipsisVerticalIcon,
	PlusIcon,
	EllipsisHorizontalIcon,
} from "@heroicons/vue/20/solid";
import SubMenu from "@/Components/SubMenu.vue";
defineProps({
	promotions: { type: Array, default: () => null },
});

const emit = defineEmits(["editPromotion", "deletePromotion"]);

const clicked = (action, model) => {
	if (action === "Edit") {
		emit("editPromotion", model);
	} else if (action === "Delete") {
		emit("deletePromotion", model);
	}
};
</script>
<template>
	<body class="-mx-4 mt-4 flow-root sm:mx-0 w-full px-4">
		<table v-if="promotions.length > 0" class="min-w-full">
			<colgroup></colgroup>
			<thead
				class="border-b border-gray-300 text-gray-900 dark:text-gray-100 dark:border-gray-200/50"
			>
				<tr>
					<th
						scope="col"
						class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 dark:text-gray-100 sm:pl-0"
					>
						Position
					</th>
					<th
						scope="col"
						class="hidden px-3 py-3.5 text-right text-sm font-semibold text-gray-900 dark:text-gray-100 sm:table-cell"
					>
						Start
					</th>
					<th
						scope="col"
						class="hidden px-3 py-3.5 text-right text-sm font-semibold text-gray-900 dark:text-gray-100 sm:table-cell"
					>
						End
					</th>
				</tr>
			</thead>
			<tbody>
				<tr
					v-for="promotion in promotions"
					:key="promotion.id"
					class="border-b border-gray-200 dark:border-gray-400/30"
				>
					<td class="max-w-0 py-2 pl-2 pr-3 text-sm sm:pl-0 w-2/5">
						<div class="font-medium text-gray-900 dark:text-gray-100">
							{{ promotion.name }}
						</div>
						<div class="mt-1 truncate text-gray-500 text-xs dark:text-gray-100">
							{{ promotion.remarks }}
						</div>
					</td>
					<td
						class="hidden p-1 text-right text-xs text-gray-500 dark:text-gray-100 sm:table-cell w-1/4"
					>
						{{ promotion.start_date }}
					</td>
					<td
						class="hidden p-1 text-right text-xs text-gray-500 dark:text-gray-100 sm:table-cell w-1/4"
					>
						{{ promotion.end_date }}
					</td>
					<td class="w-8 flex justify-end">
						<!-- <EllipsisVerticalIcon class="w-4 text-right" /> -->
						<SubMenu
							v-if="
								$page.props.permissions.includes('update staff') ||
								$page.props.permissions.includes('delete_staff')
							"
							:items="['Edit', 'Delete']"
							@item-clicked="(value) => clicked(value, promotion)"
						/>
					</td>
				</tr>
			</tbody>
		</table>
		<div
			v-else
			class="px-4 py-6 text-sm font-bold text-gray-400 dark:text-gray-100 tracking-wider text-center"
		>
			No promotions found.
		</div>
	</body>
</template>
