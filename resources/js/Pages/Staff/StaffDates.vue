<script setup>
import { format, formatDistance } from "date-fns";
import ToolTip from "@/Components/ToolTip.vue";
import { ChevronRightIcon } from "@heroicons/vue/20/solid";

defineProps({
	staff: { type: Object, required: true },
});
const formattedDob = (dateString) => {
	if (!dateString || dateString == null) return "Not provided";
	const date = new Date(dateString);
	return format(date, "dd MMMM, yyyy");
};

let getAge = (dateString) => {
	if (!dateString || dateString == null) return "Not provided";
	const date = new Date(dateString);
	return formatDistance(date, new Date(), { addSuffix: true });
};
</script>
<template>
	<main
		class="p-4 shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-600/80 sm:mx-0 sm:rounded-lg bg-gray-50 dark:bg-gray-500"
	>
		<h2
			class="text-md tracking-wide font-semibold leading-6 text-gray-900 dark:text-white"
		>
			Important dates
		</h2>
		<dl class="mt-6 grid grid-cols-1 gap-y-6 text-sm leading-6 sm:grid-cols-2">
			<div class="sm:pr-4">
				<dt class="text-gray-500 dark:text-gray-300">Date Employed:</dt>
				{{ " " }}
				<dd class="text-gray-700 dark:text-gray-100">
					<time :datetime="staff?.hire_date">{{
						formattedDob(staff?.hire_date)
					}}</time>
					<div class="text-xs">{{ getAge(staff?.hire_date) }}</div>
				</dd>
			</div>
			<div class="mt-2 sm:mt-0 sm:pl-4">
				<dt class="text-gray-500 dark:text-gray-300">Retirement Date:</dt>
				{{ " " }}
				<dd class="text-gray-700 dark:text-gray-100">
					<time :datetime="staff.retirement_date">{{
						formattedDob(staff.retirement_date)
					}}</time>
					<div class="text-xs">
						{{ getAge(staff?.retirement_date) }}
					</div>
				</dd>
			</div>

			<div
				v-if="staff.ranks.length > 0"
				class="mt-6 border-t border-gray-900/5 dark:border-gray-200/30 pt-6 sm:pr-4"
			>
				<dt class="font-semibold text-gray-900 dark:text-white">
					Current Rank
				</dt>
				<dd class="mt-2 text-gray-500 dark:text-gray-300">
					<span class="font-medium text-gray-900 dark:text-white">
						{{ staff.ranks[0]?.name }}
					</span>

					<br />
					{{ formattedDob(staff?.ranks[0]?.start_date) }}
					<span class="text-xs">
						{{ staff.ranks[0]?.distance }}
					</span>
					<br />
					{{ staff[0]?.remarks }}
				</dd>
			</div>
			<div
				v-else
				class="mt-6 border-t border-gray-900/5 dark:border-gray-200/30 pt-6 sm:pr-4 text-gray-900 dark:text-white"
			>
				No Rank rank available
			</div>
			<div
				v-if="staff.units.length > 0"
				class="mt-8 sm:mt-6 sm:border-t sm:border-gray-900/5 dark:border-gray-200/30 sm:pl-4 sm:pt-6"
			>
				<dt class="font-semibold text-gray-900 dark:text-white">
					Current Posting
				</dt>
				<dd class="mt-2 text-gray-500 dark:text-gray-300">
					<div class="flex space-x-2">
						<ToolTip :tooltip="staff.units[0]?.department">
							<div
								class="font-xl text-gray-900 dark:text-gray-100 font-semibold tracking-wider"
							>
								{{ staff.units[0]?.department_short_name }}
							</div>
						</ToolTip>
						<span><ChevronRightIcon class="w-5 h-5" /></span>
						<span class="font-medium text-gray-900 dark:text-white">{{
							staff.units[0]?.unit_name
						}}</span>
					</div>
					<p>
						{{ staff.units[0]?.start_date }}
						<span class="text-xs">
							{{ staff.units[0]?.distance }}
						</span>
					</p>
				</dd>
			</div>
			<div
				v-else
				class="mt-6 border-t border-gray-900/5 dark:border-gray-200/30 pt-6 sm:pr-4 text-gray-900 dark:text-white"
			>
				No Posting posting available
			</div>
		</dl>
	</main>
</template>
