<script setup>
import { Head, Link } from "@inertiajs/vue3";
import { format, differenceInYears } from "date-fns";
import {
	BriefcaseIcon,
	BuildingOffice2Icon,
	MagnifyingGlassIcon,
	ChevronLeftIcon,
	ChevronRightIcon,
} from "@heroicons/vue/24/outline";
defineProps({
	institution: Object,
	staff: Object,
});

const formattedDob = (dateString) => {
	const date = new Date(dateString);
	return format(date, "d MMMM, yyyy");
};

let getAge = (dateString) => {
	const date = new Date(dateString);
	return differenceInYears(new Date(), date);
};
</script>
<template>
	{{ institution }}
	<div class="min-w-full flex flex-wrap p-4 justify-end">
		<div class="w-full right-0 mt-2 sm:w-1/2">
			<div class="bg-white sm:rounded-lg overflow-hidden shadow-lg">
				<div class="text-center bg-gray-600 border-b flex sm:block">
					<div
						class="h-48 w-1/3 sm:h-16 sm:w-16 sm:rounded-full bg-white grid place-content-center sm:mx-auto text-5xl sm:text-2xl font-bold text-gray-500"
					>
						{{ staff.initials }}
					</div>
					<div class="space-y-1 py-4">
						<p class="text-2xl sm:text-lg font-semibold text-gray-50">
							{{ staff.name }}
						</p>
						<div class="flex flex-wrap justify-around py-2">
							<p class="text-sm text-gray-100 w-full pb-2">
								Staff No.: {{ staff.staff_number }} ({{
									staff.old_staff_number
								}})
							</p>

							<p
								class="text-sm text-gray-100 w-1/3"
								:title="'born ' + formattedDob(staff.dob)"
							>
								Age: {{ getAge(staff.dob) }} years
							</p>

							<p
								class="text-sm text-gray-100 w-2/3"
								:title="getAge(staff.hire_date) + ' years employed'"
							>
								Employed:
								{{ formattedDob(staff.hire_date) }}
							</p>
						</div>
						<p class="text-sm text-gray-100">
							{{ staff.email }}
						</p>
					</div>
				</div>
				<div class="border-b sm:flex">
					<Link
						:href="
							route('unit.show', {
								unit: staff.unit.id,
							})
						"
						class="px-4 py-2 hover:bg-gray-100 flex"
					>
						<div class="text-green-600 flex items-center">
							<BuildingOffice2Icon class="w-5 h-5" />
						</div>
						<div class="pl-3">
							<p class="text-sm font-medium text-gray-800 leading-none">Unit</p>
							<p class="text-xs text-gray-500">
								{{ staff.unit.name }}
							</p>
						</div>
					</Link>
					<Link
						:href="route('job.show', { job: staff.current_job_id })"
						class="px-4 py-2 hover:bg-gray-100 flex"
					>
						<div class="text-gray-600">
							<BriefcaseIcon class="h-5 w-5" />
						</div>
						<div class="pl-3">
							<p class="text-sm font-medium text-gray-800 leading-none">Rank</p>
							<p class="text-xs text-gray-500">
								{{ staff.current_job }}
							</p>
						</div>
					</Link>
				</div>
			</div>
		</div>
	</div>
</template>
