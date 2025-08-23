<script setup>
import { Link, usePage } from "@inertiajs/vue3";
import DeleteDependentModal from "@/Components/DeleteDependentModal.vue";
import AddDependentModal from "@/Pages/Staff/AddDependentModal.vue";
import { MagnifyingGlassIcon, UserPlusIcon } from "@heroicons/vue/24/outline";
import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";
defineProps({
	staff: Object,
});

const page = usePage();
const permissions = computed(() => page.props.value?.auth.permissions);

let addDependent = () => {
	showAddDepModal.value = true;
};
let showDeleteDepModal = ref(false);
let dependentToDelete = ref(null);
let showAddDepModal = ref(false);
let deleteDependents = (id) => {
	dependentToDelete.value = id;
	showDeleteDepModal.value = true;
};
let editDependent = (id) => {};

let showPerson = (id) => {
	router.get(route("person.show", { person: id }));
};
</script>
<template>
	<div class="px-4 py-5 sm:px-6 bg-white">
		<h3 class="text-lg font-medium leading-6 text-gray-900">
			Employee Dependents
		</h3>
		<div class="overflow-x-auto relative shadow-md sm:rounded-lg">
			<div
				class="flex justify-end items-center px-4 py-1 bg-white dark:bg-gray-800"
			>
				<!-- <div>
                        <button
                            id="dropdownActionButton"
                            data-dropdown-toggle="dropdownAction"
                            class="inline-flex items-center text-gray-500 bg-white border border-gray-300 focus:outline-none hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 font-medium rounded-lg text-sm px-3 py-1.5 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:border-gray-600 dark:focus:ring-gray-700"
                            type="button"
                        >
                            <span class="sr-only">Action button</span>
                            Action
                            <svg
                                class="ml-2 w-3 h-3"
                                aria-hidden="true"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 9l-7 7-7-7"
                                ></path>
                            </svg>
                        </button>

                        <div
                            id="dropdownAction"
                            class="hidden z-10 w-44 bg-white rounded divide-y divide-gray-100 shadow dark:bg-gray-700 dark:divide-gray-600"
                        >
                            <ul
                                class="py-1 text-sm text-gray-700 dark:text-gray-200"
                                aria-labelledby="dropdownActionButton"
                            >
                                <li>
                                    <a
                                        href="#"
                                        class="block py-2 px-4 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white"
                                        >Reward</a
                                    >
                                </li>
                                <li>
                                    <a
                                        href="#"
                                        class="block py-2 px-4 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white"
                                        >Promote</a
                                    >
                                </li>
                                <li>
                                    <a
                                        href="#"
                                        class="block py-2 px-4 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white"
                                        >Activate account</a
                                    >
                                </li>
                            </ul>
                            <div class="py-1">
                                <a
                                    href="#"
                                    class="block py-2 px-4 text-sm text-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 dark:text-gray-200 dark:hover:text-white"
                                    >Delete User</a
                                >
                            </div>
                        </div>
                    </div> -->

				<button
					v-if="
						permissions?.includes('update staff') ||
						permissions?.includes('delete staff')
					"
					type="button"
					class="text-white bg-green-700 hover:bg-green-800 focus:outline-none focus:ring-4 focus:ring-green-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center mr-2 mb-2 dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-800 inline-flex items-center"
					@click.stop.prevent="addDependent"
				>
					<UserPlusIcon class="w-5 h-5 mr-2" />
					Add Dependent
				</button>
			</div>
			<table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
				<thead
					class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400"
				>
					<tr>
						<!-- <th scope="col" class="p-4">
                                <div class="flex items-center">
                                    <input
                                        id="checkbox-all-search"
                                        type="checkbox"
                                        class="w-4 h-4 text-green-600 bg-gray-100 rounded border-gray-300 focus:ring-green-500 dark:focus:ring-green-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                                    />
                                    <label for="checkbox-all-search" class="sr-only"
                                        >checkbox</label
                                    >
                                </div>
                            </th> -->
						<th scope="col" class="py-3 px-6">Name</th>
						<th scope="col" class="py-3 px-6">Relation</th>
						<th scope="col" class="py-3 px-6">Action</th>
					</tr>
				</thead>
				<tbody>
					<tr
						v-for="person in staff.dependents"
						:key="person.id"
						class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 dark:hover:bg-gray-600"
					>
						<!-- <td class="p-4 w-4">
                                <div class="flex items-center">
                                    <input
                                        id="checkbox-table-search-1"
                                        type="checkbox"
                                        class="w-4 h-4 text-green-600 bg-gray-100 rounded border-gray-300 focus:ring-green-500 dark:focus:ring-green-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                                    />
                                    <label
                                        for="checkbox-table-search-1"
                                        class="sr-only"
                                        >checkbox</label
                                    >
                                </div>
                            </td> -->
						<th
							scope="row"
							class="flex items-center py-4 px-6 text-gray-900 whitespace-nowrap dark:text-white hover:cursor-pointer"
							@click="showPerson(person.person_id)"
						>
							<!-- <img
                                    class="w-10 h-10 rounded-full"
                                    src="/docs/images/people/profile-picture-1.jpg"
                                    :alt="person.name"
                                /> -->
							<div class="pl-3">
								<div class="text-base font-semibold" v-text="person.name"></div>
								<div class="font-normal text-gray-500">
									{{ person.gender }} |
									{{ person.dob }}
								</div>
							</div>
						</th>
						<td class="py-4 px-6" v-text="person.relation"></td>

						<td class="py-4 px-6">
							<!-- Modal toggle -->
							<button
								type="button"
								class="font-medium text-green-600 dark:text-green-500 hover:underline"
								@click.prevent="addDependent"
							>
								Edit
							</button>
							<button
								type="button"
								class="font-medium text-red-600 dark:text-red-500 hover:underline ml-2"
								@click.prevent="
									deleteDependents({
										id: person.id,
										name: person.name,
										staff: staff.name,
									})
								"
							>
								Delete
							</button>
						</td>
					</tr>
				</tbody>
			</table>
			<DeleteDependentModal
				:dependent="dependentToDelete"
				:is-visible="showDeleteDepModal"
				@closeModal="showDeleteDepModal = false"
			/>
			<AddDependentModal
				:staff="staff"
				:is-visible="showAddDepModal"
				@closeModal="showAddDepModal = false"
			/>
		</div>
	</div>
</template>
