<script setup>
import { Link } from "@inertiajs/vue3";
import BreadCrump from "@/Components/BreadCrump.vue";
import {
	PencilSquareIcon,
	PlusIcon,
	TrashIcon,
} from "@heroicons/vue/20/solid";
import {
	BuildingOffice2Icon,
	BuildingOfficeIcon,
	Square3Stack3DIcon,
} from "@heroicons/vue/24/outline";

const props = defineProps({
	unit: {
		type: Object,
		required: true,
	},
	permissions: {
		type: Array,
		default: () => [],
	},
});

const emit = defineEmits(["edit", "add-sub-unit", "delete"]);

// Compute breadcrumb links
const breadcrumbLinks = [
	{
		name: props.unit?.institution?.name,
		url: route("institution.show", {
			institution: props.unit?.institution?.id,
		}),
	},
	{
		name: "Departments",
		url: route("unit.index", {
			institution: props.unit?.institution?.id,
		}),
	},
	props.unit?.parent
		? {
				name: props.unit.parent.name,
				url: route("unit.show", { unit: props.unit.parent.id }),
			}
		: null,
	{ name: props.unit?.name },
].filter((link) => link && link.name);

// Get icon based on unit type
function getUnitIcon() {
	switch (props.unit?.type) {
		case "Department":
			return BuildingOffice2Icon;
		case "Division":
			return BuildingOfficeIcon;
		default:
			return Square3Stack3DIcon;
	}
}

// Get badge color based on unit type
function getTypeBadgeClass() {
	switch (props.unit?.type) {
		case "Department":
			return "bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400";
		case "Division":
			return "bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400";
		default:
			return "bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300";
	}
}
</script>

<template>
	<section class="mb-8">
		<!-- Breadcrumb -->
		<BreadCrump :links="breadcrumbLinks" />

		<!-- Header -->
		<div
			class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mt-4"
		>
			<div class="flex items-start gap-4">
				<!-- Unit Icon -->
				<div
					class="hidden sm:flex rounded-lg bg-green-100 dark:bg-green-900/30 p-3"
				>
					<component
						:is="getUnitIcon()"
						class="h-8 w-8 text-green-600 dark:text-green-400"
					/>
				</div>

				<div>
					<div class="flex items-center gap-3">
						<h1
							class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-gray-100"
						>
							{{ unit?.name }}
						</h1>
						<span
							v-if="unit?.type"
							:class="[
								'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
								getTypeBadgeClass(),
							]"
						>
							{{ unit.type }}
						</span>
					</div>

					<!-- Subtitle with parent and institution -->
					<div
						class="mt-1 flex flex-wrap items-center gap-x-2 text-sm text-gray-500 dark:text-gray-400"
					>
						<template v-if="unit?.parent">
							<Link
								:href="route('unit.show', { unit: unit.parent.id })"
								class="hover:text-green-600 dark:hover:text-green-400"
							>
								{{ unit.parent.name }}
							</Link>
							<span class="text-gray-300 dark:text-gray-600">&bull;</span>
						</template>
						<Link
							v-if="unit?.institution"
							:href="
								route('institution.show', {
									institution: unit.institution.id,
								})
							"
							class="hover:text-green-600 dark:hover:text-green-400"
						>
							{{ unit.institution.name }}
						</Link>
					</div>
				</div>
			</div>

			<!-- Action Buttons -->
			<div class="flex items-center gap-2 flex-wrap">
				<button
					v-if="permissions?.includes('edit unit')"
					type="button"
					class="inline-flex items-center gap-x-1.5 rounded-md bg-white dark:bg-gray-800 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-gray-100 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700"
					@click="emit('edit')"
				>
					<PencilSquareIcon class="-ml-0.5 h-5 w-5 text-gray-400" />
					Edit
				</button>
				<button
					v-if="permissions?.includes('create unit')"
					type="button"
					class="inline-flex items-center gap-x-1.5 rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600 dark:bg-green-700 dark:hover:bg-green-600"
					@click="emit('add-sub-unit')"
				>
					<PlusIcon class="-ml-0.5 h-5 w-5" />
					Add Sub-Unit
				</button>
				<button
					v-if="permissions?.includes('delete unit')"
					type="button"
					class="inline-flex items-center gap-x-1.5 rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600 dark:bg-red-700 dark:hover:bg-red-600"
					@click="emit('delete')"
				>
					<TrashIcon class="-ml-0.5 h-5 w-5" />
					Delete
				</button>
			</div>
		</div>
	</section>
</template>
