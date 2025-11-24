<script setup>
import { Head, router, usePage } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import Modal from "@/Components/NewModal.vue";
import {
	ExclamationTriangleIcon,
	CheckCircleIcon,
} from "@heroicons/vue/24/outline";

const page = usePage();
const permissions = computed(() => page.props?.auth.permissions);

const props = defineProps({
	staff: {
		type: Array,
		required: true,
	},
});

const processing = ref(false);
const processingStaffId = ref(null);
const showFixModal = ref(false);
const showBulkFixModal = ref(false);
const selectedStaff = ref(null);

const breadcrumbLinks = [
	{
		name: "Data Integrity",
		href: route("data-integrity.index"),
	},
	{
		name: "Multiple Active Ranks",
	},
];

const openFixModal = (staff) => {
	selectedStaff.value = staff;
	showFixModal.value = true;
};

const closeFixModal = () => {
	showFixModal.value = false;
	selectedStaff.value = null;
};

const fixSingle = () => {
	if (!selectedStaff.value) return;

	processingStaffId.value = selectedStaff.value.id;
	processing.value = true;
	showFixModal.value = false;

	router.post(
		route("data-integrity.multiple-ranks.fix", {
			staff: selectedStaff.value.id,
		}),
		{},
		{
			preserveScroll: true,
			onFinish: () => {
				processing.value = false;
				processingStaffId.value = null;
				selectedStaff.value = null;
			},
		},
	);
};

const openBulkFixModal = () => {
	showBulkFixModal.value = true;
};

const closeBulkFixModal = () => {
	showBulkFixModal.value = false;
};

const fixAll = () => {
	processing.value = true;
	showBulkFixModal.value = false;

	router.post(
		route("data-integrity.multiple-ranks.bulk-fix"),
		{},
		{
			preserveScroll: true,
			onFinish: () => {
				processing.value = false;
			},
		},
	);
};

const canFix = computed(() =>
	permissions.value?.includes("data-integrity.fix"),
);
</script>

<template>
	<MainLayout>
		<Head title="Staff with Multiple Active Ranks" />
		<main class="max-w-7xl mx-auto sm:px-6 lg:px-8">
			<BreadCrumpVue :links="breadcrumbLinks" />
			<div class="overflow-hidden shadow-sm sm:rounded-lg px-6">
				<div class="py-6">
					<!-- Header -->
					<div class="mb-6 flex items-center justify-between">
						<div>
							<h1
								class="text-3xl font-bold text-gray-900 dark:text-gray-100"
							>
								Staff with Multiple Active Ranks
							</h1>
							<p
								class="mt-2 text-sm text-gray-600 dark:text-gray-400"
							>
								{{ staff.length }} staff
								{{
									staff.length === 1 ? "member has" : "members have"
								}}
								multiple ranks with no end date
							</p>
						</div>
						<button
							v-if="canFix && staff.length > 0"
							type="button"
							:disabled="processing"
							class="rounded-md bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600 disabled:opacity-50 disabled:cursor-not-allowed dark:bg-green-700 dark:hover:bg-green-600"
							@click="openBulkFixModal"
						>
							{{
								processing
									? "Fixing All..."
									: `Fix All (${staff.length})`
							}}
						</button>
					</div>

					<!-- No Issues State -->
					<div
						v-if="staff.length === 0"
						class="rounded-lg bg-green-50 dark:bg-green-900/20 p-8 text-center"
					>
						<CheckCircleIcon
							class="mx-auto h-12 w-12 text-green-600 dark:text-green-400"
						/>
						<h3
							class="mt-4 text-lg font-semibold text-green-900 dark:text-green-100"
						>
							No Issues Found
						</h3>
						<p class="mt-2 text-sm text-green-700 dark:text-green-300">
							All staff members have proper rank assignments.
						</p>
					</div>

					<!-- Staff List -->
					<div v-else class="space-y-6">
						<div
							v-for="member in staff"
							:key="member.id"
							class="rounded-lg border-2 border-yellow-200 dark:border-yellow-800 bg-yellow-50 dark:bg-yellow-900/20 p-6"
						>
							<div
								class="flex items-start justify-between gap-4"
							>
								<div class="flex-1">
									<div class="flex items-start gap-3">
										<ExclamationTriangleIcon
											class="h-6 w-6 flex-shrink-0 text-yellow-600 dark:text-yellow-400"
										/>
										<div class="flex-1">
											<h3
												class="text-lg font-semibold text-yellow-900 dark:text-yellow-100"
											>
												{{ member.name }}
											</h3>
											<p
												class="text-sm text-yellow-700 dark:text-yellow-300"
											>
												Staff #{{ member.staff_number }}
												<span v-if="member.file_number">
													| File #{{ member.file_number }}
												</span>
											</p>
											<p
												class="mt-1 text-sm font-medium text-yellow-800 dark:text-yellow-200"
											>
												{{
													member.active_ranks_count
												}}
												active ranks found
											</p>
										</div>
									</div>

									<!-- Ranks List -->
									<div class="mt-4 space-y-2 pl-9">
										<div
											v-for="(
												rank, index
											) in member.ranks"
											:key="rank.pivot_id"
											class="rounded-md bg-white dark:bg-gray-800 p-3 shadow-sm"
										>
											<div
												class="flex items-center justify-between"
											>
												<div>
													<p
														class="font-medium text-gray-900 dark:text-gray-100"
													>
														{{ rank.name }}
														<span
															v-if="index === 0"
															class="ml-2 inline-flex items-center rounded-full bg-green-100 dark:bg-green-900 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:text-green-200"
														>
															Most Recent
														</span>
													</p>
													<p
														class="text-sm text-gray-500 dark:text-gray-400"
													>
														Started:
														{{
															rank.start_date_formatted
														}}
													</p>
												</div>
											</div>
										</div>
									</div>
								</div>

								<!-- Fix Button -->
								<button
									v-if="canFix"
									type="button"
									:disabled="
										processing &&
										processingStaffId === member.id
									"
									class="flex-shrink-0 rounded-md bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600 disabled:opacity-50 disabled:cursor-not-allowed dark:bg-green-700 dark:hover:bg-green-600"
									@click="openFixModal(member)"
								>
									{{
										processing &&
										processingStaffId === member.id
											? "Fixing..."
											: "Fix"
									}}
								</button>
							</div>
						</div>
					</div>
				</div>
			</div>
		</main>

		<!-- Single Fix Modal -->
		<Modal :show="showFixModal" @close="closeFixModal">
			<div class="sm:flex sm:items-start">
				<div
					class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-yellow-100 dark:bg-yellow-900/30 sm:mx-0 sm:h-10 sm:w-10"
				>
					<ExclamationTriangleIcon
						class="h-6 w-6 text-yellow-600 dark:text-yellow-400"
						aria-hidden="true"
					/>
				</div>
				<div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
					<h3
						class="text-lg font-semibold leading-6 text-gray-900 dark:text-gray-100"
					>
						Fix Rank Assignments
					</h3>
					<div class="mt-2">
						<p class="text-sm text-gray-500 dark:text-gray-400">
							Are you sure you want to fix rank assignments for
							<strong class="text-gray-900 dark:text-gray-100">{{
								selectedStaff?.name
							}}</strong
							>?
						</p>
						<div
							v-if="selectedStaff"
							class="mt-4 rounded-md bg-gray-50 dark:bg-gray-800 p-3"
						>
							<p
								class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
							>
								Current Status:
							</p>
							<ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
								<li>
									• {{ selectedStaff.active_ranks_count }} active ranks
									found
								</li>
								<li>
									• Most recent:
									<strong>{{
										selectedStaff.ranks[0]?.name
									}}</strong>
									will remain active
								</li>
								<li>
									• {{ selectedStaff.active_ranks_count - 1 }} older
									rank(s) will have end dates set
								</li>
							</ul>
						</div>
						<p
							class="mt-3 text-sm text-yellow-700 dark:text-yellow-300"
						>
							This action will set the end date for older ranks to one
							day before the most recent promotion.
						</p>
					</div>
				</div>
			</div>
			<div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse gap-3">
				<button
					type="button"
					:disabled="processing"
					class="inline-flex w-full justify-center rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 disabled:opacity-50 disabled:cursor-not-allowed sm:w-auto"
					@click="fixSingle"
				>
					{{ processing ? "Fixing..." : "Fix Rank Assignments" }}
				</button>
				<button
					type="button"
					class="mt-3 inline-flex w-full justify-center rounded-md bg-white dark:bg-gray-600 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-gray-100 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-500 hover:bg-gray-50 dark:hover:bg-gray-500 sm:mt-0 sm:w-auto"
					@click="closeFixModal"
				>
					Cancel
				</button>
			</div>
		</Modal>

		<!-- Bulk Fix Modal -->
		<Modal :show="showBulkFixModal" @close="closeBulkFixModal">
			<div class="sm:flex sm:items-start">
				<div
					class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-yellow-100 dark:bg-yellow-900/30 sm:mx-0 sm:h-10 sm:w-10"
				>
					<ExclamationTriangleIcon
						class="h-6 w-6 text-yellow-600 dark:text-yellow-400"
						aria-hidden="true"
					/>
				</div>
				<div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
					<h3
						class="text-lg font-semibold leading-6 text-gray-900 dark:text-gray-100"
					>
						Fix All Rank Assignments
					</h3>
					<div class="mt-2">
						<p class="text-sm text-gray-500 dark:text-gray-400">
							Are you sure you want to fix rank assignments for all
							<strong class="text-gray-900 dark:text-gray-100">{{
								staff.length
							}}</strong>
							staff members with multiple active ranks?
						</p>
						<div class="mt-4 rounded-md bg-gray-50 dark:bg-gray-800 p-3">
							<p
								class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
							>
								This operation will:
							</p>
							<ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
								<li>• Process {{ staff.length }} staff members</li>
								<li>• Keep only the most recent rank active for each</li>
								<li>
									• Set end dates for all older rank assignments
								</li>
								<li>• Cannot be undone automatically</li>
							</ul>
						</div>
						<p
							class="mt-3 text-sm text-yellow-700 dark:text-yellow-300 font-medium"
						>
							⚠️ This is a bulk operation that affects multiple
							records.
						</p>
					</div>
				</div>
			</div>
			<div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse gap-3">
				<button
					type="button"
					:disabled="processing"
					class="inline-flex w-full justify-center rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 disabled:opacity-50 disabled:cursor-not-allowed sm:w-auto"
					@click="fixAll"
				>
					{{ processing ? "Fixing All..." : `Fix All (${staff.length})` }}
				</button>
				<button
					type="button"
					class="mt-3 inline-flex w-full justify-center rounded-md bg-white dark:bg-gray-600 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-gray-100 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-500 hover:bg-gray-50 dark:hover:bg-gray-500 sm:mt-0 sm:w-auto"
					@click="closeBulkFixModal"
				>
					Cancel
				</button>
			</div>
		</Modal>
	</MainLayout>
</template>
