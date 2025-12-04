<script setup>
import { ref, watch } from "vue";
import { Link } from "@inertiajs/vue3";
import Modal from "@/Components/NewModal.vue";
import { XMarkIcon, UserCircleIcon } from "@heroicons/vue/24/outline";

const props = defineProps({
	show: {
		type: Boolean,
		default: false,
	},
	title: {
		type: String,
		default: "Staff List",
	},
	staff: {
		type: Array,
		default: () => [],
	},
	loading: {
		type: Boolean,
		default: false,
	},
});

const emit = defineEmits(["close"]);

function closeModal() {
	emit("close");
}
</script>

<template>
	<Modal :show="show" max-width="2xl" @close="closeModal">
		<div class="p-6">
			<!-- Header -->
			<div class="flex items-center justify-between mb-6">
				<h2
					class="text-xl font-bold text-gray-900 dark:text-gray-100"
				>
					{{ title }}
					<span
						v-if="!loading"
						class="ml-2 text-sm font-normal text-gray-500 dark:text-gray-400"
					>
						({{ staff.length }} staff)
					</span>
				</h2>
				<button
					type="button"
					class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300"
					@click="closeModal"
				>
					<XMarkIcon class="h-6 w-6" />
				</button>
			</div>

			<!-- Loading State -->
			<div
				v-if="loading"
				class="flex items-center justify-center py-12"
			>
				<div
					class="animate-spin rounded-full h-8 w-8 border-b-2 border-green-600"
				></div>
				<span class="ml-3 text-gray-500 dark:text-gray-400">
					Loading staff...
				</span>
			</div>

			<!-- Empty State -->
			<div
				v-else-if="staff.length === 0"
				class="text-center py-12"
			>
				<UserCircleIcon
					class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500"
				/>
				<p class="mt-2 text-gray-500 dark:text-gray-400">
					No staff found matching this criteria.
				</p>
			</div>

			<!-- Staff List -->
			<div v-else class="space-y-2 max-h-96 overflow-y-auto">
				<Link
					v-for="person in staff"
					:key="person.id"
					:href="route('staff.show', person.id)"
					class="flex items-center gap-4 p-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
				>
					<!-- Avatar -->
					<div class="flex-shrink-0">
						<img
							v-if="person.image"
							:src="`/storage/${person.image}`"
							:alt="person.name"
							class="h-10 w-10 rounded-full object-cover"
						/>
						<div
							v-else
							class="h-10 w-10 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center"
						>
							<span
								class="text-sm font-medium text-gray-600 dark:text-gray-300"
							>
								{{ person.name?.charAt(0) || "?" }}
							</span>
						</div>
					</div>

					<!-- Details -->
					<div class="flex-1 min-w-0">
						<p
							class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate"
						>
							{{ person.name }}
						</p>
						<p
							class="text-sm text-gray-500 dark:text-gray-400 truncate"
						>
							{{ person.rank || "No rank" }}
							<span v-if="person.unit">
								&middot; {{ person.unit }}
							</span>
						</p>
					</div>

					<!-- Staff Number -->
					<div class="flex-shrink-0 text-right">
						<span
							class="inline-flex items-center rounded-md bg-gray-100 dark:bg-gray-700 px-2 py-1 text-xs font-medium text-gray-600 dark:text-gray-300"
						>
							{{ person.staff_number || "N/A" }}
						</span>
					</div>
				</Link>
			</div>

			<!-- Footer -->
			<div
				v-if="staff.length > 0"
				class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700"
			>
				<p class="text-xs text-gray-500 dark:text-gray-400 text-center">
					Showing first {{ staff.length }} results. Click on a staff member to view details.
				</p>
			</div>
		</div>
	</Modal>
</template>
