<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, Link } from "@inertiajs/inertia-vue3";
import BreezeInput from "@/Components/Input.vue";
import { ref, watch } from "vue";
import debounce from "lodash/debounce";
import { Inertia } from "@inertiajs/inertia";
import Pagination from "../../Components/Pagination.vue";
import { format, differenceInYears, formatDistanceStrict } from "date-fns";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import InfoCard from "@/Components/InfoCard.vue";
import { PlusIcon } from "@heroicons/vue/24/outline";
import NoItem from "@/Components/NoItem.vue";
import Modal from "@/Components/NewModal.vue";
import AddStaffForm from "./AddStaffForm.vue";
import { useToggle } from "@vueuse/core";
import Avatar from "../Person/partials/Avatar.vue";

let props = defineProps({
	staff: Object,
	filters: Object,
});

let openDialog = ref(false);

const formSubmitted = () => {
	toggle();
	// window.location= route('staff.show', { staff: })
};
let toggle = useToggle(openDialog);

let search = ref(props.filters.search);

watch(
	search,
	debounce(function (value) {
		Inertia.get(
			route("staff.index"),
			{ search: value },
			{ preserveState: true, replace: true },
		);
	}, 300),
);

let openStaff = (staff) => {
	Inertia.visit(route("staff.show", { staff: staff }));
};

let formatDate = (dateString) => {
	const date = new Date(dateString);
	return format(date, "dd MMMM, yyyy");
	// return new Intl.DateTimeFormat("en-GB", { dateStyle: "full" }).format(date);
};

let getAge = (dateString) => {
	const date = new Date(dateString);

	return differenceInYears(new Date(), date);
};
let BreadCrumpLinks = [
	{
		name: "Staff",
		url: "",
	},
];
</script>

<template>
	<Head title="Staff" />

	<MainLayout>
		<div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
			<BreadCrumpVue :links="BreadCrumpLinks" />
			<div class="overflow-hidden shadow-sm sm:rounded-lg">
				<div class="px-6 border-b border-gray-200">
					<div class="sm:flex items-center justify-between my-2">
						<FormKit
							v-model="search"
							prefix-icon="search"
							type="search"
							placeholder="Search staff..."
							autofocus
						/>
						<InfoCard title="Staff" :value="staff.total" link="#" />

						<a
							class="ml-auto flex items-center gap-x-1 rounded-md bg-green-600 dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600"
							href="#"
							@click.prevent="toggle()"
						>
							<PlusIcon class="-ml-1.5 h-5 w-5" aria-hidden="true" />
							Onboard Staff
						</a>
					</div>

					<div class="flex flex-col mt-6">
						<div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
							<div
								class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8"
							>
								<div
									v-if="staff.total > 0"
									class="overflow-hidden border-b border-gray-200 rounded-md shadow-md"
								>
									<table
										class="min-w-full overflow-x-scroll divide-y divide-gray-200"
									>
										<thead class="bg-gray-50 dark:bg-gray-700">
											<tr>
												<th
													scope="col"
													class="px-6 py-3 text-xs font-bold tracking-widest text-left text-gray-800 dark:text-gray-50 uppercase"
												>
													Name
												</th>

												<th
													scope="col"
													class="px-6 py-3 text-xs font-bold tracking-widest text-left text-gray-800 dark:text-gray-50 uppercase"
												>
													Employment
												</th>
												<th
													scope="col"
													class="px-6 py-3 text-xs font-bold tracking-widest text-left text-gray-800 dark:text-gray-50 uppercase"
												>
													Retirement
												</th>
												<th
													scope="col"
													class="px-6 py-3 text-xs font-bold tracking-widest text-left text-gray-800 dark:text-gray-50 uppercase"
												>
													Current Rank
												</th>
												<th
													scope="col"
													class="px-6 py-3 text-xs font-bold tracking-widest text-left text-gray-800 dark:text-gray-50 uppercase"
												>
													Current Unit
												</th>
											</tr>
										</thead>
										<tbody
											class="bg-white dark:bg-gray-500 divide-y divide-gray-200 dark:divide-gray-400"
										>
											<tr
												v-for="person in staff.data"
												:key="person.id"
												class="cursor-pointer transition-all hover:bg-gray-100 dark:hover:bg-gray-600 hover:shadow-lg"
												@click="openStaff(person.id)"
											>
												<td class="px-6 py-4 whitespace-nowrap">
													<div class="flex items-center">
														<Avatar :image="person.image" :initials="person.initials" />
														<div class="ml-4">
															<div
																class="text-green-900 dark:text-gray-50 font-semibold"
															>
																{{ person.name }}
															</div>
															<div
																class="text-xs text-gray-500 dark:text-gray-200"
															>
																{{ person.gender }}
																&bull;
																{{ person.dob }}
																({{ person.dob_distance }})
															</div>
														</div>
													</div>
												</td>
												<td class="px-6 py-4 whitespace-nowrap">
													<div class="text-green-800 dark:text-gray-200">
														{{ person.staff_number }}

														{{
															person.file_number
																? " / " + person.file_number
																: ""
														}}
													</div>
													<div class="text-xs text-gray-900 dark:text-gray-200">
														{{ person.hire_date }}
														&bull;
														{{ person.hire_date_distance }}
													</div>
												</td>
												<td
													class="px-6 py-4 text-sm text-gray-500 dark:text-gray-200 whitespace-nowrap"
													:title="person.current_rank?.start_date_distance"
												>
													<div>
														<div
															class="font-semibold text-green-900 dark:text-gray-200"
														>
															{{ person.retirement_date }}
														</div>
														<p class="text-xs text-gray-500 dark:text-gray-200">
															{{ person.retirement_date_distance }}
														</p>
													</div>
												</td>
												<td
													class="px-6 py-4 text-sm text-gray-500 dark:text-gray-200 whitespace-nowrap"
													:title="person.current_rank?.start_date_distance"
												>
													<div v-if="person.current_rank">
														<div
															class="font-semibold text-green-900 dark:text-gray-200"
														>
															{{ person.current_rank.name }}
														</div>
														<p class="text-xs text-gray-500 dark:text-gray-200">
															{{ person.current_rank.start_date_distance }}
														</p>

														<p
															v-if="person.current_rank.remarks"
															class="text-green-800 dark:text-gray-200 text-xs tracking-wider"
														>
															{{ person.current_rank.remarks }}
														</p>
													</div>
												</td>
												<td
													class="px-6 py-4 text-sm font-medium whitespace-nowrap dark:text-gray-200"
													:title="person.current_unit?.start_date_distance"
												>
													<div v-if="person.current_unit">
														<div
															class="font-semibold text-green-800 dark:text-gray-50"
														>
															{{ person.current_unit.name }}
														</div>
														<div
															class="text-xs text-gray-500 dark:text-gray-200"
														>
															{{ person.current_unit.start_date }}
														</div>
														<div
															class="text-xs text-gray-500 dark:text-gray-200"
														>
															{{ person.current_unit.start_date_distance }}
														</div>
													</div>
												</td>
											</tr>
										</tbody>
									</table>
									<Pagination :records="staff" />
								</div>
								<NoItem v-else name="Staff" />
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</MainLayout>
	<Modal @close="toggle()" :show="openDialog">
		<AddStaffForm @form-submitted="formSubmitted()" />
	</Modal>
	<!-- <AddStaff @closeDialog="openDialog = false" :open="openDialog" /> -->
</template>
