<script setup>
import SubMenu from "@/Components/SubMenu.vue";
// import { usePage } from "@inertiajs/vue3";
// import { computed } from "vue";
defineProps({
	permissions: { type: Array, default: () => null },
});

// const page = usePage();
// const permissions = computed(() => page.props?.auth.permissions);

const emit = defineEmits(["deletePermission"]);

const clicked = (action, model) => {
	if (action === "Revoke") {
		emit("deletePermission", model);
	}
};
</script>
<template>
	<body
		class="my-4 flow-root sm:mx-0 w-full px-4 bg-green-50 dark:bg-gray-500 rounded-b-lg"
	>
		<table v-if="permissions?.length > 0" class="min-w-full">
			<colgroup></colgroup>
			<thead
				class="border-b border-gray-300 text-gray-900 dark:text-gray-100 dark:border-gray-200/50"
			>
				<tr>
					<th
						scope="col"
						class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-green-900 dark:text-gray-100 sm:pl-0"
					>
						Permissions name
					</th>
					<th
						scope="col"
						class="hidden px-3 py-3.5 text-sm font-semibold text-green-900 dark:text-gray-100 sm:table-cell"
					>
						Date granted
					</th>

					<th
						scope="col"
						class="hidden px-3 text-sm font-semibold text-gray-900 dark:text-gray-100 sm:table-cell"
					></th>
				</tr>
			</thead>
			<tbody>
				<tr
					v-for="permission in permissions"
					:key="permission"
					class="border-b border-gray-200 dark:border-gray-400/30"
				>
					<td class="max-w-0 py-2 pl-2 pr-3 text-sm sm:pl-0 w-3/5">
						<div class="font-medium text-green-900 dark:text-gray-100">
							{{ permission.name }}
						</div>
						<!-- <div class="mt-1 truncate text-gray-500 text-xs dark:text-gray-100">
							{{ permission.start_date }}
						</div> -->
					</td>
					<td
						class="hidden p-1 text-xs text-green-800 dark:text-gray-100 sm:table-cell text-center"
					>
						{{ permission.start_date }}
					</td>

					<td class="w-8 flex justify-end">
						<SubMenu
							v-if="
								permissions?.includes('update permission') ||
								permissions?.includes('delete permission')
							"
							:can-revoke="permissions?.includes('update permission')"
							:items="['Revoke']"
							@item-clicked="(value) => clicked(value, permission)"
						/>
					</td>
				</tr>
			</tbody>
		</table>
		<div
			v-else
			class="px-4 py-6 text-sm font-bold text-gray-400 dark:text-gray-100 tracking-wider text-center"
		>
			No permissions found.
		</div>
	</body>
</template>
