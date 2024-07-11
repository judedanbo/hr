<script setup>
import { Link } from "@inertiajs/inertia-vue3";

import NoItem from "@/Components/NoItem.vue";

defineProps({
	type: String,
	subs: Object,
	searchText: String,
});

defineEmits(["update:modelValue"]);
</script>

<template>
	<section class="w-full">
		<header>
			<p
				class="font-bold text-xl px-8 py-4 text-gray-700 dark:text-white tracking-wide"
			>
				<span>Sub Units ({{ subs.subs_number }})</span>

				<span class="text-lg text-gray-500 dark:text-white ml-2">
					<!-- ({{ subs }}) -->
				</span>
			</p>
		</header>
		<main
			class="shadow-lg sm:rounded-2xl bg-white dark:bg-gray-700 w-full max-h-screen overflow-y-auto"
		>
			<!-- <div " class=""> -->
			<table
				v-if="subs"
				class="divide-y divide-gray-300 dark:divide-gray-600 w-full"
			>
				<thead class="dark:bg-gray-900">
					<tr>
						<th
							scope="col"
							class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 dark:text-gray-50 sm:pl-8"
						>
							Name
						</th>
						<th
							scope="col"
							class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-50"
						>
							Staff
						</th>
						<!-- <th
								scope="col"
								class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-50"
							>
								Sub units
							</th> -->
					</tr>
				</thead>
				<tbody class="bg-white dark:bg-gray-700">
					<tr
						v-for="(subUnit, index) in subs.subs"
						:key="index"
						class="even:bg-gray-50 dark:even:bg-gray-500"
					>
						<td
							class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 dark:text-white sm:pl-8"
						>
							<Link :href="route('unit.show', { unit: subUnit.id })"
								>{{ subUnit.name }}
							</Link>
						</td>
						<td
							class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-200"
						>
							{{ subUnit.staff_count }}
						</td>
						<!-- <td
								class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-200"
							>
								{{ subUnit.subs }}
							</td> -->
					</tr>
				</tbody>
			</table>
			<NoItem v-else :name="type" />
			<!-- </div> -->
		</main>
	</section>

	<!-- </div> -->
</template>
