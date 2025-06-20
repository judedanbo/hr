<script setup>
import { Link } from "@inertiajs/inertia-vue3";
import Avatar from "../Person/partials/Avatar.vue";
import NoItem from "@/Components/NoItem.vue";
import { ArrowDownTrayIcon } from "@heroicons/vue/20/solid";

const props = defineProps({
	unit: {
		type: Object,
		required: true,
	},
	download: {
		type: Boolean,
		default: false,
	},
});

defineEmits(["update:modelValue"]);
const exportToExcel = () => {
	window.location = route("export.unit.staff", { unit: props.unit.id });
};
</script>

<template>
	<section class="w-full">
		<header class="flex justify-between items-center">
			<p
				class="font-bold text-2xl px-8 py-4 text-gray-700 dark:text-white tracking-wide"
			>
				<span>Staff ({{ unit.staff_number }})</span>
			</p>
			<a
				v-if="download"
				class="ml-auto flex items-center gap-x-1 rounded-md bg-green-600 dark:bg-gray-800 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-green-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600 dark:hover:bg-gray-900"
				href="#"
				@click.prevent="exportToExcel()"
			>
				<ArrowDownTrayIcon class="-ml-1.5 h-5 w-5" aria-hidden="true" />
				Export Staff list
			</a>
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
									class="truncate text-xl font-medium leading-6 text-gray-700 dark:text-white space-y-2"
								>
									{{ sta.name }}
									<div class="font-mono leading-6 text-gray-400 mt-3">
										First Appointment:
										{{ sta.hire_date }}
									</div>
									<div class="font-mono leading-6 text-gray-400">
										Staff No.: {{ sta.staff_number }} / File No.:
										{{ sta.file_number }}
									</div>
								</div>
							</Link>
						</td>
						<td class="p-4 hidden md:block space-y-2">
							<div class="font-mono leading-6 text-gray-400">
								{{ sta.rank?.name }}
							</div>
							<div class="font-mono leading-6 text-gray-400">
								{{ sta.rank?.start_date }}
							</div>
							<div class="font-mono leading-6 text-gray-400">
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
