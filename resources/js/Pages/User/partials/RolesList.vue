<script setup>
import SubMenu from "@/Components/SubMenu.vue";
import { usePage } from "@inertiajs/inertia-vue3";
import { computed } from "vue";
defineProps({
	roles: { type: Array, default: () => null },
});

const emit = defineEmits(["deleteRole"]);

const page = usePage();
const permissions = computed(() => page.props.value.auth.permissions);

const clicked = (action, model) => {
	if (action === "Revoke") {
		emit("deleteRole", model);
	}
};
</script>
<template>
	<body class="mt-4 flow-root sm:mx-0 w-full px-4 bg-green-50 dark:bg-gray-500">
		<table v-if="roles?.length > 0" class="min-w-full">
			<colgroup></colgroup>
			<thead
				class="border-b border-gray-300 text-gray-900 dark:text-gray-100 dark:border-gray-200/50"
			>
				<tr>
					<th
						scope="col"
						class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 dark:text-gray-100 sm:pl-0"
					>
						Roles
					</th>
					<th
						scope="col"
						class="hidden px-3 py-3.5 text-sm font-semibold text-gray-900 dark:text-gray-100 sm:table-cell"
					>
						Start
					</th>

					<th
						scope="col"
						class="hidden px-3 text-sm font-semibold text-gray-900 dark:text-gray-100 sm:table-cell"
					></th>
				</tr>
			</thead>
			<tbody>
				<tr
					v-for="role in roles"
					:key="role.id"
					class="border-b border-gray-200 dark:border-gray-400/30"
				>
					<td class="max-w-0 py-2 pl-2 pr-3 text-sm sm:pl-0 w-2/5">
						<div class="font-medium text-gray-900 dark:text-gray-100">
							{{ role.name }}
						</div>
						<div class="mt-1 truncate text-gray-500 text-xs dark:text-gray-100">
							{{ role.remarks }}
						</div>
					</td>
					<td
						class="hidden p-1 text-xs text-gray-500 dark:text-gray-100 sm:table-cell"
					>
						{{ role.start_date }}
					</td>

					<td class="w-8 flex justify-end">
						<SubMenu
							v-if="permissions.includes('assign roles to user')"
							:items="['Revoke']"
							:can-revoke="permissions.includes('update permission')"
							:can-edit="permissions.includes('assign roles to user')"
							@item-clicked="(value) => clicked(value, role)"
						/>
					</td>
				</tr>
			</tbody>
		</table>
		<div
			v-else
			class="px-4 py-6 text-sm font-bold text-gray-400 dark:text-gray-100 tracking-wider text-center"
		>
			No roles found for this user.
		</div>
	</body>
</template>
