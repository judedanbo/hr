<script setup>
import { Link } from "@inertiajs/inertia-vue3";
import Avatar from "../Person/partials/Avatar.vue";
import NoItem from "@/Components/NoItem.vue";

defineProps({
	unit: {
		type: Object,
		required: true,
	},
});

defineEmits(["update:modelValue"]);
</script>

<template>
	<section class="w-full">
		<header>
			<p
				class="font-bold text-xl px-8 py-4 text-gray-700 dark:text-white tracking-wide"
			>
				<span>Staff ({{ unit.staff_number }})</span>
			</p>
		</header>
		<main
			class="max-h-screen overflow-y-auto shadow-lg sm:rounded-2xl bg-white dark:bg-gray-700 w-full"
		>
			<table
				v-if="unit.staff_number > 0"
				class="whitespace-nowrap text-left w-full"
			>
				<thead
					class="border-b border-white/10 text-sm leading-6 text-green-950 uppercase dark:text-white dark:bg-gray-900 bg-green-100"
				>
					<tr class="py-3">
						<th
							scope="col"
							class="py-2 pl-4 pr-8 font-semibold sm:pl-6 lg:pl-8"
						>
							Staff
						</th>
						<th
							scope="col"
							class="hidden py-2 pl-0 pr-8 font-semibold md:table-cell"
						>
							Rank
						</th>
						<!-- <th
									scope="col"
									class="hidden py-2 pl-0 pr-8 font-semibold sm:table-cell"
								>
									Unit
								</th> -->
					</tr>
				</thead>
				<tbody class="divide-y divide-white/5">
					<tr
						v-for="sta in unit.staff"
						:key="sta.id"
						class="hover:bg-green-50 dark:hover:bg-gray-600"
					>
						<td class="p-4 sm:pl-6 lg:pl-8">
							<Link
								:href="route('staff.show', { staff: sta.id })"
								class="flex gap-x-4"
							>
								<Avatar :initials="sta.initials" :image="sta.image" />
								<div
									class="truncate text-sm font-medium leading-6 text-gray-700 dark:text-white"
								>
									{{ sta.name }}
									<div class="font-mono text-sm leading-6 text-gray-400">
										First Appointment:
										{{ sta.hire_date }}
									</div>
									<div class="font-mono text-sm leading-6 text-gray-400">
										Staff No.: {{ sta.staff_number }} / File No.:
										{{ sta.file_number }}
									</div>
								</div>
							</Link>
						</td>
						<td class="p-4 hidden md:block">
							<div class="font-mono text-sm leading-6 text-gray-400">
								{{ sta.rank?.name }}
							</div>
							<div class="font-mono text-sm leading-6 text-gray-400">
								{{ sta.rank?.start_date }}
							</div>
							<div class="font-mono text-sm leading-6 text-gray-400">
								{{ sta.rank?.remarks }}
							</div>
						</td>
					</tr>
				</tbody>
			</table>
			<NoItem v-else name="Staff" />
		</main>
	</section>
</template>
