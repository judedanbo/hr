<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, Link } from "@inertiajs/inertia-vue3";
import { formatDistance } from "date-fns";
import Address from "./Address.vue";
import { useToggle } from "@vueuse/core";
import { ref } from "vue";
import Dependents from "../Staff/Dependents.vue";
import { Menu, MenuButton, MenuItem, MenuItems } from "@headlessui/vue";
import { EllipsisVerticalIcon } from "@heroicons/vue/20/solid";
import Avatar from "../Person/partials/Avatar.vue";
import Summary from "./Summary.vue";
import NewModal from "@/Components/NewModal.vue";
import EditPersonForm from "@/Pages/Person/partials/EditPersonForm.vue";


let showEditForm = ref(false);

let toggleEditForm = useToggle(showEditForm);

let props = defineProps({
	person: Object,
	address: Object,
	contacts: Array,
	filters: Object,
	dependents: Array,
	staff: Object,
});

let BreadcrumbLinks = [
	{ name: "Person", url: "/staff" },
	{ name: props.person.name, url: "/" },
];
</script>
<template>
	<Head :title="person.name" />

	<MainLayout>
		<main>
			<header
				class="relative isolate pt-4 border dark:border-gray-600 rounded-lg"
			>
				<div class="absolute inset-0 -z-10 overflow-hidden" aria-hidden="true">
					<div
						class="absolute left-16 top-full -mt-16 transform-gpu opacity-50 blur-3xl xl:left-1/2 xl:-ml-80"
					>
						<div
							class="aspect-[1154/678] w-[72.125rem] bg-gradient-to-br from-green-100 dark:from-gray-100 to-yellow-200 dark:to-gray-800 dark:border-gray-700 rounded-3xl"
							style="
								clip-path: polygon(
									100% 38.5%,
									82.6% 100%,
									60.2% 37.7%,
									52.4% 32.1%,
									47.5% 41.8%,
									45.2% 65.6%,
									27.5% 23.4%,
									0.1% 35.3%,
									17.9% 0%,
									27.7% 23.4%,
									76.2% 2.5%,
									74.2% 56%,
									100% 38.5%
								);
							"
						/>
					</div>
					<div class="absolute inset-x-0 bottom-0 h-px bg-gray-900/5" />
				</div>

				<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
					<div
						class="mx-auto flex max-w-2xl items-center justify-between gap-x-8 lg:mx-0 lg:max-w-none"
					>
						<div class="flex items-center gap-x-6">
							<Avatar
								v-if="person.image"
								:initials="person.initials"
								:image="person.image"
								size="lg"
							/>
							<div
								v-else
								class="flex justify-center items-center h-24 w-24 flex-none rounded-lg ring-1 ring-green-400/60 dark:ring-gray-400 text-5xl text-green-400/50 dark:text-gray-300 font-bold tracking-wide"
							>
								{{ person.initials }}
							</div>
							<h1>
								<div
									class="mt-1 text-base font-semibold leading-6 text-gray-900 dark:text-white"
								>
									{{ person.name }}
								</div>
							</h1>
						</div>
						<div class="flex items-center gap-x-4 sm:gap-x-6">
							<button
								class="rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600"
								@click="toggleEditForm()"
							>
								Edit
							</button>

							<Menu as="div" class="relative sm:hidden">
								<MenuButton class="-m-3 block p-3">
									<span class="sr-only">More</span>
									<EllipsisVerticalIcon
										class="h-5 w-5 text-gray-500 dark:text-gray-300"
										aria-hidden="true"
									/>
								</MenuButton>

								<transition
									enter-active-class="transition ease-out duration-100"
									enter-from-class="transform opacity-0 scale-95"
									enter-to-class="transform opacity-100 scale-100"
									leave-active-class="transition ease-in duration-75"
									leave-from-class="transform opacity-100 scale-100"
									leave-to-class="transform opacity-0 scale-95"
								>
									<MenuItems
										class="absolute right-0 z-10 mt-0.5 w-32 origin-top-right rounded-md bg-white py-2 shadow-lg ring-1 ring-gray-900/5 focus:outline-none"
									>
										<MenuItem v-slot="{ active }">
											<button
												type="button"
												:class="[
													active ? 'bg-gray-50' : '',
													'block w-full px-3 py-1 text-left text-sm leading-6 text-gray-900 dark:text-white',
												]"
											>
												Copy URL
											</button>
										</MenuItem>
										<MenuItem v-slot="{ active }">
											<a
												href="#"
												:class="[
													active ? 'bg-gray-50' : '',
													'block px-3 py-1 text-sm leading-6 text-gray-900 dark:text-white',
												]"
												>Edit</a
											>
										</MenuItem>
									</MenuItems>
								</transition>
							</Menu>
						</div>
					</div>
				</div>
			</header>

			<div class="mx-auto max-w-7xl py-4 xl:px-8">
				<div
					class="mx-auto lg:grid max-w-2xl grid-cols-1 grid-rows-1 items-start gap-x-8 gap-y-8 lg:mx-0 lg:max-w-none lg:grid-cols-3"
				>
					<!-- <StaffDates class="w-full lg:flex-1" :staff="staff" /> -->
					<div class="md:col-start-3 flex flex-wrap gap-4 w-full">
						<!-- Employment summary -->

						<Summary :person="person" />
						<!-- Contact information -->
						<Address
							:address="address"
							:contacts="contacts"
							:person="person.id"
						/>
						<Dependents :dependents="dependents" />
					</div>
					<div
						class="col-start-1 col-span-3 lg:col-span-2 lg:row-span-2 lg:row-end-2 flex flex-wrap gap-4"
					></div>
				</div>
			</div>
		</main>
		<NewModal :show="showEditForm" @close="toggleEditForm()">
				<EditPersonForm :person-id="person.id" @form-submitted="toggleEditForm()" />
		</NewModal>
	</MainLayout>
</template>
