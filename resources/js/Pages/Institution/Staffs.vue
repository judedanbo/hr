<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, Link } from "@inertiajs/vue3";
import BreezeInput from "@/Components/Input.vue";
import { ref, watch } from "vue";
import { debouncedWatch } from "@vueuse/core";
import { router } from "@inertiajs/vue3";
import Pagination from "../../Components/Pagination.vue";
import {
	BriefcaseIcon,
	BuildingOffice2Icon,
	MagnifyingGlassIcon,
	ChevronLeftIcon,
	ChevronRightIcon,
} from "@heroicons/vue/24/outline";

import BreadCrumpVue from "@/Components/BreadCrump.vue";

let props = defineProps({
	staff: Object,
	institution: Object,
	// departments: Array,
	filters: Object,
});

let search = ref(props.filters.search);

debouncedWatch(
	search,
	() => {
		router.get(
			route("institution.staffs", {
				institution: props.institution.id,
			}),
			{ search: value },
			{ preserveState: true, replace: true, preserveScroll: true },
		);
	},
	{ debounce: 300 },
);

let BreadCrumpLinks = [
	{
		name: props.institution.name,
		url: route("institution.index", { institution: 21 }),
	},
	{
		name: "Staff",
	},
];
</script>

<template>
	<Head :title="institution.name + ' Staff'" />

	<MainLayout>
		<div class="py-6">
			<div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
				<BreadCrumpVue :links="BreadCrumpLinks" />
				<h2
					class="mt-4 mb-6 text-xl font-semibold leading-tight text-gray-900 dark:text-gray-50"
				>
					{{ institution.name }}
				</h2>
				<div
					class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg"
				>
					<div
						class="p-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700"
					>
						<div class="sm:flex justify-between my-6">
							<h3 class="mb-4 text-xl">
								Staff ({{ institution.staff.toLocaleString() }})
							</h3>
							<div class="mt-1 relative mx-8">
								<div
									class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
								>
									<span class="text-gray-500 sm:text-sm">
										<MagnifyingGlassIcon class="w-4 h-4" />
									</span>
								</div>
								<BreezeInput
									v-model="search"
									type="search"
									class="w-full pl-8 bg-slate-100 border-0"
									required
									autofocus
									placeholder="Search staff..."
								/>
							</div>
						</div>
						<div class="flex flex-col mt-6">
							<div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
								<div
									class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8"
								>
									<div
										class="border-b border-gray-200 dark:border-gray-700 rounded-md shadow-md"
									>
										<div v-if="staff" class="min-w-full flex flex-wrap py-4">
											<div
												v-for="stf in staff"
												:key="stf.id"
												class="w-full mx-auto right-0 mt-2 sm:w-60"
											>
												<div
													class="bg-white dark:bg-gray-800 sm:rounded-lg overflow-hidden shadow-lg"
												>
													<div class="text-center p-6 bg-gray-600 border-b">
														<div
															class="h-16 w-16 rounded-full bg-white grid place-content-center mx-auto text-2xl font-bold"
														>
															{{ stf.initials }}
														</div>

														<p class="pt-2 text-lg font-semibold text-gray-50">
															{{ stf.name }}
														</p>
														<p class="text-sm text-gray-100">
															{{ stf.email }}
														</p>
														<div class="mt-5">
															<Link
																:href="
																	route('institution.staff', {
																		staff: stf.staff_id,
																		institution: institution.id,
																	})
																"
																class="border rounded-full py-2 px-4 text-xs font-semibold text-gray-100"
															>
																View
															</Link>
														</div>
													</div>
													<div class="border-b">
														<Link
															v-for="sta_unit in stf.units"
															:key="stf.id + '_' + sta_unit.id"
															:href="
																route('unit.show', {
																	unit: sta_unit.id,
																})
															"
														>
															<a
																class="px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 flex"
															>
																<div class="text-green-600 flex items-center">
																	<BuildingOffice2Icon class="w-5 h-5" />
																</div>
																<div class="pl-3">
																	<p
																		class="text-sm font-medium text-gray-800 dark:text-gray-100 leading-none"
																	>
																		Unit
																	</p>
																	<p class="text-xs text-gray-500">
																		{{ sta_unit.name }}
																	</p>
																</div>
															</a>
														</Link>
														<Link
															:href="
																route('job.show', {
																	job: stf.current_job_id,
																})
															"
														>
															<a
																class="px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 flex"
															>
																<div class="text-gray-600">
																	<BriefcaseIcon class="h-5 w-5" />
																</div>
																<div class="pl-3">
																	<p
																		class="text-sm font-medium text-gray-800 dark:text-gray-100 leading-none"
																	>
																		Job
																	</p>
																	<p class="text-xs text-gray-500">
																		{{ stf.current_job }}
																	</p>
																</div>
															</a>
														</Link>
													</div>
												</div>
											</div>
										</div>

										<!-- <template> -->
										<div
											class="flex items-center justify-between border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-3 sm:px-6"
										>
											<div class="flex flex-1 justify-between">
												<Link
													:href="
														route('institution.staffs', {
															institution: institution.id,
															page: parseInt(filters.page) + 1,
														})
													"
													class="relative inline-flex items-center rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600"
													>Previous
												</Link>
												<div
													class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-center"
												>
													<div>
														<p class="text-sm text-gray-700">
															Showing
															{{ " " }}
															<span class="font-medium">{{
																filters.page ? filters.page * 15 - 14 : 1
															}}</span>
															{{ " " }}
															to
															{{ " " }}
															<span class="font-medium">{{
																filters.page ? parseInt(filters.page) * 15 : 15
															}}</span>
															{{ " " }}
															of
															{{ " " }}
															<span class="font-medium">{{
																institution.staff.toLocaleString()
															}}</span>
															{{ " " }}
															results
														</p>
													</div>
												</div>
												<Link
													:href="
														route('institution.staffs', {
															institution: institution.id,
															page: filters.page
																? parseInt(filters.page) + 1
																: 1,
														})
													"
													class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600"
													>Next
												</Link>
											</div>
										</div>
										<!-- </template> -->
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</MainLayout>
</template>
