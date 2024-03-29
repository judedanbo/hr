<script setup>
import Modal from "@/Components/NewModal.vue";
import { ref } from "vue";
import { useToggle } from "@vueuse/core";
import AddDependant from "./partials/AddDependant.vue";

defineProps({
	dependents: Array,
	staff_id: Number,
});

let showAddDependantForm = ref(false);
let toggleAddDependantFrom = useToggle(showAddDependantForm);
</script>
<template>
	<!-- dependent History -->
	<main class="w-full">
		<h2 class="sr-only">Staff Dependents</h2>
		<div
			class="rounded-lg bg-gray-50 dark:bg-gray-500 shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-500/80"
		>
			<dl class="flex flex-wrap">
				<div class="flex-auto pl-6 pt-6">
					<dt
						class="text-md tracking-wide font-semibold leading-6 text-gray-900 dark:text-gray-100"
					>
						Staff Dependents
					</dt>
				</div>
				<div class="flex-none self-end px-6 pt-4">
					<button
						v-if="staff_id"
						@click.prevent="toggleAddDependantFrom()"
						class="rounded-md bg-green-50 dark:bg-gray-400 px-2 py-1 text-xs font-medium text-green-600 dark:text-gray-50 ring-1 ring-inset ring-green-600/20 dark:ring-gray-500"
					>
						Add dependent
					</button>
				</div>
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
							</tr>
						</thead>
						<tbody>
							<tr
								v-for="dependent in dependents"
								:key="dependent.id"
								class="border-b border-gray-200 dark:border-gray-200/80"
							>
								<td class="max-w-0 py-2 pl-4 pr-3 text-sm sm:pl-0">
									<div class="font-medium text-gray-900 dark:text-gray-50">
										{{ dependent.name }}
									</div>
									<div class="mt-1 truncate text-gray-500 dark:text-gray-50">
										{{ dependent.gender }}
									</div>
								</td>
								<td
									class="hidden px-3 py-2 text-right text-sm text-gray-500 dark:text-gray-50 sm:table-cell"
								>
									{{ dependent.relation }}
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
			</dl>
		</div>
		<Modal @close="toggleAddDependantFrom()" :show="showAddDependantForm">
			<AddDependant
				@formSubmitted="toggleAddDependantFrom()"
				:staff_id="staff_id"
			/>
		</Modal>
	</main>
</template>
