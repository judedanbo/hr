<script setup>
import { Link } from "@inertiajs/inertia-vue3";
import StaffItem from "./StaffItem.vue";
import StaffType from "../../StaffType/Index.vue";
import StaffStatus from "../../StaffStatus/Index.vue";
defineProps({
	staff: {
		type: Object,
		required: true,
	},
});
</script>
<template>
	<main class="w-full">
		<h2 class="sr-only">Employment Details</h2>
		<div
			class="md:rounded-lg bg-gray-50 dark:bg-gray-500 shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-500/30"
		>
			<dl
				v-for="sta in staff"
				:key="sta.staff_id"
				class="flex flex-wrap gap-5 p-4"
			>
				<div class="flex-auto pl-6 pt-6">
					<dt
						class="text-sm font-semibold leading-6 text-gray-900 dark:text-gray-100"
					>
						{{ "Staff of " + sta.institution_name }}
					</dt>
				</div>
				<div class="flex-none self-end px-6 pt-4">
					<Link
						v-if="
							$page.props.permissions.includes('update staff') ||
							$page.props.permissions.includes('delete staff')
						"
						:href="route('staff.edit', { staff: sta.staff_id })"
						class="rounded-md bg-green-50 dark:bg-gray-400 px-2 py-1 text-xs font-medium text-green-600 dark:text-gray-50 ring-1 ring-inset ring-green-600/20 dark:ring-gray-500"
					>
						Edit
					</Link>
				</div>
				<div class="mt-4 w-full border-t pt-6 px-6 flex gap-5 justify-between">
					<div
						class="gap-x-4 space-y-3 border-gray-900/5 dark:border-gray-200/30"
					>
						<StaffItem
							:item="{
								title: 'Employment Date',
								value: [sta.hire_date_dis],
							}"
						/>
						<StaffItem
							:item="{
								title: 'Staff / File Number',
								value: [sta.staff_number, sta.file_number],
							}"
						/>
					</div>
					<!-- <div>Rank History {{ sta.ranks }}</div> -->
					<div
						v-if="sta.ranks"
						class="border-t border-gray-900/5 dark:border-gray-200/30 pt-2 sm:pr-4"
					>
						<dt class="font-semibold text-gray-900 dark:text-white">
							Current Rank
						</dt>
						<dd class="mt-2 text-gray-500 dark:text-gray-300">
							<div class="text-xs">{{ sta.institution_name }}</div>
							<span class="font-medium text-gray-900 dark:text-white">{{
								sta.ranks?.name
							}}</span
							><br />
							<span v-if="sta?.ranks?.start_date">
								{{ sta?.ranks?.start_date }} -
								{{ sta?.units?.end_date ?? "Present" }}
							</span>
							<span v-else class="text-xs font-thin italic">No start date</span>
							<br />{{ sta?.remarks }}
						</dd>
					</div>
					<div
						v-if="sta.units"
						class="border-t border-gray-900/5 dark:border-gray-200/30 pt-2 sm:pr-4"
					>
						<dt class="font-semibold text-gray-900 dark:text-white">
							Current Unit
						</dt>
						<dd class="mt-2 text-gray-500 dark:text-gray-300">
							<div class="text-xs">{{ sta.units.department }}</div>
							<span class="font-medium text-gray-900 dark:text-white">{{
								sta.units?.unit_name
							}}</span
							><br />
							<span v-if="sta?.units?.start_date">
								{{ sta?.units?.start_date }} -
								{{ sta?.units?.end_date ?? "Present" }}
							</span>
							<span v-else class="text-xs font-thin italic">No start date</span>
							<br />{{ sta?.remarks }}
						</dd>
					</div>
					<!-- <div>Transfer History {{ sta.units }}</div> -->
				</div>

				<div class="flex gap-5">
					<StaffStatus
						:statuses="sta.status"
						:staff="{
							id: sta.staff_id,
							hire_date: sta.hire_date,
						}"
						:institution="sta.institution_id"
						class="flex-1"
						@close-form="toggleTransferForm()"
					/>
					<StaffType
						:types="sta.type"
						:staff="{
							id: sta.staff_id,
							hire_date: sta.hire_date,
						}"
						:institution="sta.institution_id"
						class="flex-1"
						@close-form="toggleTransferForm()"
					/>
				</div>
			</dl>
		</div>
	</main>
</template>
