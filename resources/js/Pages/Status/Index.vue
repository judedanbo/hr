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
import { PlusIcon } from "@heroicons/vue/24/outline";
import NoItem from "@/Components/NoItem.vue";
import BreezeButton from "@/Components/Button.vue";
import Modal from "@/Components/NewModal.vue";
import { useToggle } from "@vueuse/core";

let props = defineProps({
	institution: Object,
	statuses: Array,
	filters: Object,
});

let openDialog = ref(false);

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
						<!-- <InfoCard title="Staff" :value="staff.total" link="#" /> -->

						<!-- <BreezeButton @click="toggle()">Add New Staff</BreezeButton> -->
						<a
							@click.prevent="toggle()"
							href="#"
							class="ml-auto flex items-center gap-x-1 rounded-md bg-green-600 dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600"
						>
							<PlusIcon class="-ml-1.5 h-5 w-5" aria-hidden="true" />
							New Status
						</a>
					</div>

					<div class="flex flex-col mt-6">
						<div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
							<div
								class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8"
							>
								{{ statuses }}
								<div
									class="overflow-hidden border-b border-gray-200 rounded-md shadow-md"
								>
									<table
										class="min-w-full overflow-x-scroll divide-y divide-gray-200"
									>
										<thead class="bg-gray-50 dark:bg-gray-700">
											<tr>
												<th
													scope="col"
													class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 dark:text-gray-50 uppercase"
												>
													Name
												</th>
												<th
													scope="col"
													class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 dark:text-gray-50 uppercase"
												>
													Date of Birth
												</th>

												<th
													scope="col"
													class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 dark:text-gray-50 uppercase"
												>
													Current Unit
												</th>
											</tr>
										</thead>
										<tbody
											class="bg-white dark:bg-gray-500 divide-y divide-gray-200"
										>
											<tr
												v-for="status in statuses"
												:key="status.id"
												@click="openStaff(status.id)"
												class="cursor-pointer transition-all hover:bg-gray-100 dark:hover:bg-gray-600 hover:shadow-lg"
											>
												<td class="px-6 py-4 whitespace-nowrap">
													<div class="flex items-center">
														<div class="ml-4">
															<div
																class="text-sm font-medium text-gray-900 dark:text-gray-100"
															>
																{{ status.name }}
															</div>
														</div>
													</div>
												</td>
												<td class="px-6 py-4 whitespace-nowrap">
													<div class="text-sm text-gray-900 dark:text-gray-100">
														{{ status.dob }}
													</div>
												</td>

												<td
													class="px-6 py-4 text-sm font-medium whitespace-nowrap dark:text-gray-100"
												></td>
											</tr>
										</tbody>
									</table>
									<!-- <Pagination :navigation="navigation" /> -->
								</div>
								<!-- <NoItem v-else name="Staff" /> -->
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</MainLayout>
	<Modal @close="toggle()" :show="openDialog">
		<!-- <AddStaffForm @form-submitted="toggle()" /> -->
	</Modal>
	<!-- <AddStaff @closeDialog="openDialog = false" :open="openDialog" /> -->
</template>
