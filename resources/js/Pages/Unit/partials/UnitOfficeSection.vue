<script setup>
import { computed } from "vue";
import {
	BuildingOfficeIcon,
	MapPinIcon,
	GlobeAltIcon,
} from "@heroicons/vue/24/outline";
import { PencilSquareIcon, PlusIcon, XMarkIcon } from "@heroicons/vue/20/solid";

const props = defineProps({
	office: {
		type: Object,
		default: null,
	},
	canEdit: {
		type: Boolean,
		default: false,
	},
});

const emit = defineEmits(["manage", "remove"]);

// Format the office info for display
const officeType = computed(() => {
	return props.office?.type || "Unknown";
});

const officeName = computed(() => {
	return props.office?.name || "No office assigned";
});

const districtName = computed(() => {
	return props.office?.district?.name || null;
});

const regionName = computed(() => {
	return props.office?.district?.region?.name || null;
});

const hasOffice = computed(() => {
	return props.office && props.office.id;
});
</script>

<template>
	<section>
		<div class="flex items-center justify-between mb-4">
			<h2
				class="text-lg font-semibold text-gray-900 dark:text-gray-100"
			>
				Office Location
			</h2>
			<div v-if="canEdit" class="flex gap-2">
				<button
					type="button"
					class="inline-flex items-center gap-x-1.5 rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600"
					@click="emit('manage')"
				>
					<component
						:is="hasOffice ? PencilSquareIcon : PlusIcon"
						class="-ml-0.5 h-5 w-5"
						aria-hidden="true"
					/>
					{{ hasOffice ? "Change" : "Add" }} Office
				</button>
				<button
					v-if="hasOffice"
					type="button"
					class="inline-flex items-center gap-x-1.5 rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600"
					@click="emit('remove')"
				>
					<XMarkIcon class="-ml-0.5 h-5 w-5" aria-hidden="true" />
					Remove
				</button>
			</div>
		</div>

		<div
			class="relative overflow-hidden rounded-lg bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700"
		>
			<div v-if="hasOffice" class="p-6">
				<div class="flex items-start gap-4">
					<div
						class="flex-shrink-0 rounded-lg bg-green-100 dark:bg-green-900 p-3"
					>
						<BuildingOfficeIcon
							class="h-8 w-8 text-green-600 dark:text-green-400"
							aria-hidden="true"
						/>
					</div>
					<div class="flex-1 min-w-0">
						<h3
							class="text-xl font-semibold text-gray-900 dark:text-white truncate"
						>
							{{ officeName }}
						</h3>
						<p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
							{{ officeType }}
						</p>

						<div class="mt-4 space-y-2">
							<div
								v-if="districtName"
								class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300"
							>
								<MapPinIcon
									class="h-5 w-5 text-gray-400 dark:text-gray-500"
									aria-hidden="true"
								/>
								<span>{{ districtName }}</span>
							</div>
							<div
								v-if="regionName"
								class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300"
							>
								<GlobeAltIcon
									class="h-5 w-5 text-gray-400 dark:text-gray-500"
									aria-hidden="true"
								/>
								<span>{{ regionName }}</span>
							</div>
						</div>
					</div>
				</div>
			</div>

			<div v-else class="p-6 text-center">
				<BuildingOfficeIcon
					class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600"
					aria-hidden="true"
				/>
				<h3
					class="mt-2 text-sm font-semibold text-gray-900 dark:text-gray-100"
				>
					No office assigned
				</h3>
				<p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
					This unit has not been assigned to an office location.
				</p>
			</div>
		</div>
	</section>
</template>
