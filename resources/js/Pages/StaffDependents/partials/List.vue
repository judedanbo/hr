<script setup>
import SubMenu from "@/Components/SubMenu.vue";
import Avatar from "@/Pages/Person/partials/Avatar.vue";
import { Link, usePage } from "@inertiajs/inertia-vue3";
import { computed } from "vue";

const page = usePage();
const permissions = computed(() => page.props.value.auth.permissions);

const emit = defineEmits(["editDependent", "deleteDependent"]);

defineProps({
	dependents: { type: Array, default: () => [] },
});

const subMenuClicked = (action, model) => {
	if (action == "Edit") {
		emit("editDependent", model);
	}
	if (action == "Delete") {
		emit("deleteDependent", model);
	}
};
</script>
<template>
	<div class="-mx-4 mt-8 flow-root sm:mx-0 w-full px-4">
		<table v-if="dependents.length > 0" class="min-w-full">
			<colgroup>
				<col class="w-full" />
				<col class="sm:w-1/6" />
				<col class="sm:w-1/6" />
				<col class="sm:w-1/6" />
			</colgroup>
			<thead
				class="border-b border-gray-300 dark:border-gray-200/80 text-gray-900 dark:text-gray-50"
			>
				<tr>
					<th
						scope="col"
						class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 dark:text-gray-50 sm:pl-0"
					>
						Name
					</th>
					<th
						scope="col"
						class="hidden px-3 py-3.5 text-right text-sm font-semibold text-gray-900 dark:text-gray-50 sm:table-cell"
					>
						Relation
					</th>
					<th><div class="sr-only">Action</div></th>
				</tr>
			</thead>
			<tbody>
				<tr
					v-for="dependent in dependents"
					:key="dependent.id"
					class="border-b border-gray-200 dark:border-gray-200/80"
				>
					<td class="max-w-0 py-2 pl-4 pr-3 text-sm sm:pl-0">
						<section class="flex items-center">
							<Avatar :image="dependent.image" :initials="dependent.initials" />
							<div class="ml-4">
								<div class="text-green-900 dark:text-gray-50 font-semibold">
									<Link
										:href="
											route('person.show', { person: dependent.person_id })
										"
										>{{ dependent.name }}
										<!-- {{ dependent }} -->
									</Link>
								</div>
								<div class="text-xs text-gray-500 dark:text-gray-200">
									<span v-if="dependent.gender !== null">
										{{ dependent.gender }}
									</span>
									<span v-if="dependent.date_of_birth !== null">
										&bull;
										{{ dependent.date_of_birth }}
										({{ dependent.dob_distance }})
									</span>
								</div>
							</div>
						</section>
						<!-- <div class="flex items-center">
							<Avatar :image="dependent.image" :initials="dependent.initials" />
							<div class="font-medium text-gray-900 dark:text-gray-50">
								{{ dependent.name }}
							</div>
							<div class="mt-1 truncate text-gray-500 dark:text-gray-50">
								{{ dependent.gender }}
							</div>
						</div> -->
					</td>
					<td
						class="hidden px-3 py-2 text-right text-sm text-gray-500 dark:text-gray-50 sm:table-cell"
					>
						{{ dependent.relation }}
					</td>
					<td class="flex justify-end">
						<SubMenu
							v-if="
								permissions.includes('update staff') ||
								permissions.includes('delete staff')
							"
							:can-edit="permissions.includes('update staff')"
							:can-delete="permissions.includes('delete staff')"
							:items="['Edit', 'Delete']"
							@item-clicked="(action) => subMenuClicked(action, dependent)"
						/>
					</td>
				</tr>
			</tbody>
		</table>
		<div
			v-else
			class="px-4 py-6 text-sm font-bold text-gray-400 dark:text-gray-100 tracking-wider text-center"
		>
			No dependents found.
		</div>
	</div>
</template>
