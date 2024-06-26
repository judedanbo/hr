<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, Link } from "@inertiajs/inertia-vue3";
import BreezeInput from "@/Components/Input.vue";
import { ref, watch, computed } from "vue";
import { debouncedWatch } from "@vueuse/core";
import { Inertia } from "@inertiajs/inertia";
import Pagination from "../../Components/Pagination.vue";
import format from "date-fns/format";
import differenceInYears from "date-fns/differenceInYears";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import InfoCard from "@/Components/InfoCard.vue";
import { MagnifyingGlassIcon } from "@heroicons/vue/24/outline";
import NoItem from "@/Components/NoItem.vue";
import Avatar from "./partials/Avatar.vue";
import Roles from "./partials/Roles.vue";
import { useNavigation } from "@/Composables/navigation";

let props = defineProps({
	people: Object,
	filters: Object,
});

const navigation = computed(() => useNavigation(props.people));

let search = ref(props.filters.search);

debouncedWatch(
	search,
	() => {
		Inertia.get(
			route("person.index"),
			{ search: search.value },
			{ preserveState: true, replace: true },
		);
	},
	{ debounce: 300 },
);

let formatDate = (dateString) => {
	const date = new Date(dateString);
	return format(date, "EEEE dd MMMM, yyyy");
	// return new Intl.DateTimeFormat("en-GB", { dateStyle: "full" }).format(date);
};

let getAge = (dateString) => {
	const date = new Date(dateString);

	return differenceInYears(new Date(), date);
};
let BreadCrumpLinks = [
	{
		name: "Person",
		url: "",
	},
];
</script>

<template>
	<Head title="Dashboard" />

	<MainLayout>
		<div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
			<BreadCrumpVue :links="BreadCrumpLinks" />
			<div class="overflow-hidden shadow-sm sm:rounded-lg">
				<div class="px-6 border-b border-gray-200">
					<div class="sm:flex items-center justify-between my-2">
						<InfoCard title="People" :value="people.total" link="#" />
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
								class="w-full pl-8 bg-white border-0"
								required
								autofocus
								placeholder="Search People..."
							/>
						</div>
					</div>

					<div class="flex flex-col mt-6">
						<div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
							<div
								class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8"
							>
								<div
									v-if="people.total > 0"
									class="overflow-hidden border-b border-gray-200 rounded-md shadow-md"
								>
									<table
										class="min-w-full overflow-x-scroll divide-y divide-gray-200"
									>
										<thead class="bg-gray-50 dark:bg-gray-700">
											<tr>
												<th
													scope="col"
													class="px-6 py-3 text-xs font-bold tracking-widest text-left text-gray-800 dark:text-gray-100 uppercase"
												>
													Name
												</th>
												<th
													scope="col"
													class="px-6 py-3 text-xs font-bold tracking-widest text-left text-gray-800 dark:text-gray-100 uppercase"
												>
													Date of Birth
												</th>
												<!-- <th
                          scope="col"
                          class="px-6 py-3 text-xs font-bold tracking-widest text-left text-gray-800 dark:text-gray-100 uppercase"
                        >
                          SSNIT No
                        </th> -->
												<th
													scope="col"
													class="px-6 py-3 text-xs font-bold tracking-widest text-left text-gray-800 dark:text-gray-100 uppercase"
												>
													Role
												</th>
												<th role="col" class="relative px-6 py-3">
													<span class="sr-only">Edit</span>
												</th>
											</tr>
										</thead>
										<tbody
											class="bg-white dark:bg-gray-500 divide-y divide-gray-200 dark:divide-gray-400"
										>
											<tr
												v-for="person in people.data"
												:key="person.id"
												class="cursor-pointer transition-all hover:bg-gray-100 dark:hover:bg-gray-400 hover:shadow-lg"
											>
												<td
													class="px-6 py-4 text-sm font-medium whitespace-nowrap dark:text-gray-50 text-right"
												>
													<div class="flex items-center">
														<Avatar
															:initials="person.initials"
															:image="person.image"
														/>

														<div class="ml-4">
															<div
																class="text-sm font-medium text-gray-900 dark:text-gray-50"
															>
																{{ person.name }}
															</div>
															<div
																class="text-sm text-left text-gray-500 dark:text-gray-100"
															>
																{{ person.gender }}
															</div>
														</div>
													</div>
												</td>
												<td class="px-6 py-4 whitespace-nowrap">
													<div class="text-sm text-gray-900 dark:text-gray-200">
														{{ formatDate(person.dob) }}
													</div>
													<div class="text-sm text-gray-500 dark:text-gray-100">
														{{ getAge(person.dob) }}
														Years
													</div>
												</td>
												<td
													class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap"
												>
													<Roles :person="person.id" />
												</td>
												<td
													class="px-6 py-4 text-sm font-medium text-right whitespace-nowrap"
												>
													<Link
														:href="
															route('person.show', {
																person: person.id,
															})
														"
														class="text-green-600 hover:text-green-900 dark:text-gray-50"
														>Show
													</Link>
												</td>
											</tr>
										</tbody>
									</table>
									<Pagination :navigation="navigation" />
								</div>
								<NoItem v-else name="Person" />
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</MainLayout>
</template>
