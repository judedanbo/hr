<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import BreezeInput from "@/Components/Input.vue";
import { ref, watch, computed } from "vue";
import { debouncedWatch } from "@vueuse/core";
import { router, usePage } from "@inertiajs/vue3";
import Pagination from "../../Components/Pagination.vue";
import { PlusIcon } from "@heroicons/vue/24/outline";
import { useToggle } from "@vueuse/core";
import { format } from "date-fns";
import Modal from "@/Components/NewModal.vue";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import Create from "./Create.vue";
import Edit from "./Edit.vue";
import Delete from "./Delete.vue";
import FlyoutMenu from "@/Components/FlyoutMenu.vue";
import { useNavigation } from "@/Composables/navigation";
import NoPermission from "@/Components/NoPermission.vue";

const page = usePage();
const permissions = computed(() => page.props.value?.auth.permissions);

let props = defineProps({
	institutions: Object,
	filters: Object,
});
const navigation = computed(() => useNavigation(props.institutions));

const form = useForm({
	name: null,
	abbreviation: null,
	start_date: format(new Date(), "yyyy-MM-dd"),
	institution_id: null,
});

let selectedModel = ref(null);

let openCreateModal = ref(false);
let openEditModal = ref(false);
let openDeleteModal = ref(false);

let toggleCreateModal = useToggle(openCreateModal);
let toggleEditModal = useToggle(openEditModal);
let toggleDeleteModal = useToggle(openDeleteModal);

let displayEditModal = ($event, id) => {
	selectedModel.value = props.institutions.data.filter(
		(institution) => institution.id == id,
	);
	toggleEditModal();
};
let displayDeleteModal = ($event, id) => {
	selectedModel.value = props.institutions.data.filter(
		(institution) => institution.id == id,
	);
	toggleDeleteModal();
};
const submitForm = () => {
	form.post(route("institution.store"), {
		preserveScroll: true,
		onSuccess: () => {
			form.reset();
			toggleCreateModal();
		},
	});
};

let search = ref(props.filters.search);

debouncedWatch(
	search,
	() => {
		router.get(
			route("institution.index"),
			{ search: search.value },
			{ preserveState: true, replace: true, preserveScroll: true },
		);
	},
	{ debounce: 300 },
);

let BreadCrumpLinks = [
	{
		name: "Institutions",
	},
];
</script>

<template>
	<Head title="Institutions" />

	<MainLayout>
		<div
			v-if="permissions?.includes('view all institutions')"
			class="max-w-7xl mx-auto px-0 lg:px-8"
		>
			<div
				class="bg-gray-100 dark:bg-gray-600 overflow-hidden shadow-sm lg:rounded-lg w-full"
			>
				<div class="p-4">
					<BreadCrumpVue :links="BreadCrumpLinks" />
					<div class="flex justify-center items-center">
						<FormKit
							v-if="permissions?.includes('view all institutions')"
							v-model="search"
							prefix-icon="search"
							type="search"
							placeholder="Search institutions..."
							autofocus
						/>
						<a
							v-if="permissions?.includes('create institution')"
							href="#"
							class="ml-auto flex items-center gap-x-1 rounded-md bg-green-600 dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600"
							@click.prevent="toggleCreateModal()"
						>
							<PlusIcon class="-ml-1.5 h-5 w-5" aria-hidden="true" />
							New Institutions
						</a>
					</div>
					<div class="flex flex-col mt-2">
						<div class="overflow-x-auto sm:-mx-6 lg:-mx-8">
							<div
								class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8"
							>
								<div class="overflow-hidden rounded-md shadow-md">
									<table
										v-if="institutions.total > 0"
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
													Departments
												</th>
												<th
													scope="col"
													class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 dark:text-gray-50 uppercase"
												>
													Divisions
												</th>
												<th
													scope="col"
													class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 dark:text-gray-50 uppercase"
												>
													Units
												</th>
												<th
													scope="col"
													class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 dark:text-gray-50 uppercase"
												>
													Staff
												</th>

												<th role="col" class="relative px-6 py-3">
													<span class="sr-only">Edit</span>
												</th>
											</tr>
										</thead>
										<tbody class="bg-white divide-y divide-gray-200">
											<tr
												v-for="institution in institutions.data"
												:key="institution.id"
												class="transition-all hover:bg-gray-100 dark:hover:bg-gray-700 hover:shadow-lg dark:bg-gray-600"
											>
												<td class="px-6 py-2 whitespace-nowrap">
													<div class="flex items-center">
														<div
															class="flex-shrink-0 w-10 h-10 bg-gray-200 dark:bg-gray-400 rounded-full flex justify-center items-center"
														></div>

														<div class="ml-4">
															<div
																class="text-sm font-medium text-gray-900 dark:text-gray-100"
															>
																{{ institution.name }}
																{{
																	institution.abbreviation
																		? "(" + institution.abbreviation + ")"
																		: ""
																}}
															</div>
															<div class="text-sm text-gray-500"></div>
														</div>
													</div>
												</td>
												<td class="px-6 py-4 whitespace-nowrap">
													<div
														class="text-sm text-gray-900 dark:text-gray-100 text-center"
													>
														{{ institution.departments }}
													</div>
												</td>
												<td class="px-6 py-4 whitespace-nowrap">
													<div
														class="text-sm text-gray-900 dark:text-gray-100 text-center"
													>
														{{ institution.units }}
													</div>
												</td>
												<td class="px-6 py-4 whitespace-nowrap">
													<div
														class="text-sm text-gray-900 dark:text-gray-100 text-center"
													>
														{{ institution.divisions.toLocaleString() }}
													</div>
												</td>
												<td class="px-6 py-4 whitespace-nowrap">
													<div
														class="text-sm text-gray-900 dark:text-gray-100 text-center"
													>
														{{ institution.staff.toLocaleString() }}
													</div>
												</td>

												<td
													class="px-6 py-4 text-sm font-medium text-right whitespace-nowrap"
												>
													<FlyoutMenu
														name="edit"
														path="institution"
														:route_id="institution.id"
														@editItem="
															($event, id) => displayEditModal($event, id)
														"
														@deleteItem="
															($event, id) => displayDeleteModal($event, id)
														"
													/>
												</td>
											</tr>
										</tbody>
									</table>

									<Pagination :navigation="navigation" />
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<Modal :show="openCreateModal" @close="toggleCreateModal()">
				<Create @formSubmitted="toggleCreateModal()" />
			</Modal>
			<Modal :show="openEditModal" @close="toggleEditModal()">
				<Edit
					:selected-model="selectedModel[0]"
					@formSubmitted="toggleEditModal()"
				/>
			</Modal>
			<Modal :show="openDeleteModal" @close="toggleDeleteModal()">
				<Delete
					:selected-model="selectedModel[0]"
					@institutionDeleted="toggleDeleteModal()"
					@cancelDelete="toggleDeleteModal()"
				/>
			</Modal>
		</div>
		<NoPermission v-else />
	</MainLayout>
</template>

<style scoped>
/* .formkit-input{
    @apply border-none ring-1 ring-green-300 dark:ring-gray-500  focus:ring-green-600 dark:focus:ring-gray-50
} */
.formkit-prefix-icon {
	@apply text-green-600 dark:text-gray-200;
}
input::placeholder {
	@apply text-gray-400;
}
</style>
