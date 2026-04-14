<script setup>
import { ref } from "vue";
import { Link } from "@inertiajs/vue3";
import {
	BuildingOffice2Icon,
	BuildingOfficeIcon,
	Square3Stack3DIcon,
	ChevronDownIcon,
} from "@heroicons/vue/24/outline";
import { ChevronRightIcon } from "@heroicons/vue/20/solid";

// Collapsed state - default expanded
const isCollapsed = ref(true);

const toggleCollapse = () => {
	isCollapsed.value = !isCollapsed.value;
};

const props = defineProps({
	subs: {
		type: Array,
		required: true,
	},
	parentName: {
		type: String,
		default: "",
	},
	canDownload: {
		type: Boolean,
		default: false,
	},
});

// Get icon based on unit type
function getUnitIcon(type) {
	switch (type) {
		case "Department":
			return BuildingOffice2Icon;
		case "Division":
			return BuildingOfficeIcon;
		default:
			return Square3Stack3DIcon;
	}
}

// Get icon background color based on type
function getIconBgClass(type) {
	switch (type) {
		case "Department":
			return "bg-purple-100 dark:bg-purple-900/30";
		case "Division":
			return "bg-blue-100 dark:bg-blue-900/30";
		default:
			return "bg-green-100 dark:bg-green-900/30";
	}
}

// Get icon color based on type
function getIconColorClass(type) {
	switch (type) {
		case "Department":
			return "text-purple-600 dark:text-purple-400";
		case "Division":
			return "text-blue-600 dark:text-blue-400";
		default:
			return "text-green-600 dark:text-green-400";
	}
}

// Calculate gender percentage for progress bar
function getMalePercentage(sub) {
	const total = sub.staff_count || 0;
	const male = sub.male_staff || 0;
	if (total === 0) return 50;
	return (male / total) * 100;
}
</script>

<template>
	<section v-if="subs && subs.length > 0">
		<!-- Collapsible Header -->
		<button
			type="button"
			class="flex items-center justify-between w-full mb-4 group"
			@click="toggleCollapse"
		>
			<h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
				Sub-Units
				<span class="text-sm font-normal text-gray-500 dark:text-gray-400">
					({{ subs.length }})
				</span>
			</h2>
			<ChevronDownIcon
				class="h-5 w-5 text-gray-500 dark:text-gray-400 transition-transform duration-200 group-hover:text-gray-700 dark:group-hover:text-gray-300"
				:class="{ '-rotate-180': isCollapsed }"
			/>
		</button>

		<!-- Collapsible Content -->
		<transition
			enter-active-class="transition-all duration-300 ease-out"
			enter-from-class="opacity-0 max-h-0"
			enter-to-class="opacity-100 max-h-[2000px]"
			leave-active-class="transition-all duration-200 ease-in"
			leave-from-class="opacity-100 max-h-[2000px]"
			leave-to-class="opacity-0 max-h-0"
		>
			<div
				v-show="!isCollapsed"
				class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 overflow-hidden"
			>
				<Link
					v-for="sub in subs"
					:key="sub.id"
					:href="route('unit.show', { unit: sub.id })"
					class="group bg-white dark:bg-gray-800 rounded-lg shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700 p-4 hover:shadow-md hover:ring-green-500 dark:hover:ring-green-400 transition-all duration-200"
				>
					<!-- Header with icon and name -->
					<div class="flex items-start justify-between">
						<div class="flex items-center gap-3">
							<div class="rounded-lg p-2" :class="getIconBgClass(sub.type)">
								<component
									:is="getUnitIcon(sub.type)"
									class="h-5 w-5"
									:class="getIconColorClass(sub.type)"
								/>
							</div>
							<div class="min-w-0 flex-1">
								<h3
									class="text-sm font-semibold text-gray-900 dark:text-gray-100 group-hover:text-green-600 dark:group-hover:text-green-400 truncate"
								>
									{{ sub.name }}
								</h3>
								<p
									v-if="sub.type"
									class="text-xs text-gray-500 dark:text-gray-400"
								>
									{{ sub.type }}
								</p>
							</div>
						</div>
						<ChevronRightIcon
							class="h-5 w-5 text-gray-400 group-hover:text-green-500 transition-colors"
						/>
					</div>

					<!-- Stats Grid -->
					<dl class="mt-4 grid grid-cols-3 gap-2 text-center">
						<div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 px-2 py-2">
							<dt class="text-xs text-gray-500 dark:text-gray-400">Staff</dt>
							<dd
								class="text-lg font-semibold text-gray-900 dark:text-gray-100"
							>
								{{ sub.staff_count?.toLocaleString() || 0 }}
							</dd>
						</div>
						<div class="rounded-lg bg-blue-50 dark:bg-blue-900/20 px-2 py-2">
							<dt class="text-xs text-blue-600 dark:text-blue-400">Male</dt>
							<dd
								class="text-lg font-semibold text-blue-700 dark:text-blue-300"
							>
								{{ sub.male_staff?.toLocaleString() || 0 }}
							</dd>
						</div>
						<div class="rounded-lg bg-pink-50 dark:bg-pink-900/20 px-2 py-2">
							<dt class="text-xs text-pink-600 dark:text-pink-400">Female</dt>
							<dd
								class="text-lg font-semibold text-pink-700 dark:text-pink-300"
							>
								{{ sub.female_staff?.toLocaleString() || 0 }}
							</dd>
						</div>
					</dl>

					<!-- Gender Breakdown Bar -->
					<div v-if="sub.staff_count > 0" class="mt-3 flex items-center gap-2">
						<div
							class="flex-1 h-2 bg-pink-200 dark:bg-pink-900/50 rounded-full overflow-hidden"
						>
							<div
								class="h-full bg-blue-500 dark:bg-blue-400 rounded-full transition-all duration-300"
								:style="{ width: `${getMalePercentage(sub)}%` }"
							></div>
						</div>
						<span
							class="text-xs text-gray-500 dark:text-gray-400 w-16 text-right"
						>
							{{ Math.round(getMalePercentage(sub)) }}% M
						</span>
					</div>

					<!-- Sub-units indicator -->
					<div
						v-if="sub.subs > 0"
						class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700"
					>
						<span class="text-xs text-gray-500 dark:text-gray-400">
							{{ sub.subs }} nested sub-unit{{ sub.subs > 1 ? "s" : "" }}
						</span>
					</div>
				</Link>
			</div>
		</transition>
	</section>
</template>
