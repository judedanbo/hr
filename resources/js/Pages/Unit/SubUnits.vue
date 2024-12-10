<script setup>
import { Link } from "@inertiajs/inertia-vue3";
import { ArrowDownTrayIcon } from "@heroicons/vue/20/solid";
import NoItem from "@/Components/NoItem.vue";

defineProps({
	type: String,
	subs: Object,
	searchText: String,
});

defineEmits(["update:modelValue"]);
const exportToExcel = (subUnitId) => {
	// console.log(unit);
	window.location = route("export.unit.staff", { unit: subUnitId });
};
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
							class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900 dark:text-gray-50"
						>
							Staff
						</th>
						<th
							scope="col"
							class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900 dark:text-gray-50"
						>
							Male
						</th>
						<th
							scope="col"
							class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900 dark:text-gray-50"
						>
							Total
						</th>
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
							<div class="flex items-center gap-x-2">
								<a
									class="flex gap-x-1 rounded-md dark:bg-gray-600 p-3 text-sm font-semibold text-green-800 dark:text-white shadow-sm hover:bg-green-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600 dark:hover:bg-gray-900"
									href="#"
									tool-tip="download list"
									@click.prevent="exportToExcel(subUnit.id)"
								>
									<ArrowDownTrayIcon class="h-5 w-5" aria-hidden="true" />
								</a>

								<Link :href="route('unit.show', { unit: subUnit.id })"
									>{{ subUnit.name }}
								</Link>
							</div>
						</td>
						<td
							class="whitespace-nowrap px-5 py-4 text-sm text-gray-500 dark:text-gray-200 text-right"
						>
							{{ subUnit.male_staff }}
						</td>
						<td
							class="whitespace-nowrap px-5 py-4 text-sm text-gray-500 dark:text-gray-200 text-right"
						>
							{{ subUnit.female_staff }}
						</td>
						<td
							class="whitespace-nowrap px-3 py-4 text-right text-sm text-gray-500 dark:text-gray-200"
						>
							{{ subUnit.staff_count }}
						</td>
					</tr>
				</tbody>
			</table>
			<NoItem v-else :name="type" />
			<!-- </div> -->
		</main>
	</section>

	<!-- </div> -->
</template>
