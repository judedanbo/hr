<script setup>
import { computed } from "vue";

const props = defineProps({
	dependents: { type: Array, default: () => null },
});

const rows = computed(() => props.dependents ?? []);
const hasDependents = computed(() => rows.value.length > 0);
</script>

<template>
	<section
		class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-sm"
	>
		<header class="flex justify-between items-center mb-3">
			<h3 class="text-sm font-bold text-gray-900 dark:text-gray-50">
				Dependents
			</h3>
			<span class="text-[11px] text-gray-400 dark:text-gray-500"
				>View only</span
			>
		</header>

		<div v-if="hasDependents" class="overflow-x-auto">
			<table class="w-full text-sm">
				<thead>
					<tr
						class="text-left text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700"
					>
						<th class="py-2 pr-3">Name</th>
						<th class="py-2 pr-3">Relation</th>
						<th class="py-2 pr-3">Gender</th>
						<th class="py-2">Age</th>
					</tr>
				</thead>
				<tbody class="divide-y divide-gray-100 dark:divide-gray-700">
					<tr
						v-for="d in rows"
						:key="d.id"
						class="text-gray-900 dark:text-gray-100"
					>
						<td class="py-2 pr-3 font-medium truncate">{{ d.name }}</td>
						<td
							class="py-2 pr-3 text-gray-600 dark:text-gray-400 whitespace-nowrap"
						>
							{{ d.relation ?? "—" }}
						</td>
						<td
							class="py-2 pr-3 text-gray-600 dark:text-gray-400 whitespace-nowrap"
						>
							{{ d.gender ?? "—" }}
						</td>
						<td class="py-2 text-gray-600 dark:text-gray-400 whitespace-nowrap">
							{{ d.age ?? "—" }}
						</td>
					</tr>
				</tbody>
			</table>
			<p class="mt-3 text-[11px] italic text-gray-500 dark:text-gray-400">
				Need changes? Contact HR.
			</p>
		</div>

		<div v-else class="py-3">
			<p class="text-sm text-gray-500 dark:text-gray-400">
				Contact HR to add dependants.
			</p>
		</div>
	</section>
</template>
