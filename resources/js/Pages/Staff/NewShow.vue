<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head } from "@inertiajs/inertia-vue3";
import StaffDates from "./StaffDates.vue";
import Summary from "@/Pages/Person/Summary.vue";
import PromotionHistory from "./PromotionHistory.vue";
import TransferHistory from "./TransferHistory.vue";
import StaffStatus from "../StaffStatus/Index.vue";
import StaffType from "../StaffType/Index.vue";
import StaffPosition from "../StaffPosition/Index.vue";
import Qualifications from "./PersonQualifications.vue";
import Dependents from "@/Pages/StaffDependents/Index.vue";
// import Dependents from "./Dependents.vue";
import Address from "./Address.vue";
import StaffIdentities from "./StaffIdentities.vue";
import Notes from "./Notes.vue";
import { useToggle } from "@vueuse/core";
import { ref } from "vue";
import { PlusIcon } from "@heroicons/vue/20/solid";
import Avatar from "../Person/partials/Avatar.vue";
import NewModal from "@/Components/NewModal.vue";
import EditStaffForm from "./EditStaffForm.vue";
import EditContactForm from "./EditContactForm.vue";

let showPromotionForm = ref(false);
let showTransferForm = ref(false);
let openEditModal = ref(false);

let toggle = useToggle(openEditModal);

let togglePromotionForm = useToggle(showPromotionForm);
let toggleTransferForm = useToggle(showTransferForm);
const formattedDob = (dob) => {
	if (!dob) return "";
	return new Date(dob).toLocaleDateString("en-GB", {
		day: "numeric",
		month: "short",
		year: "numeric",
	});
};

let props = defineProps({
	user: { type: Object, default: () => null },
	person: { type: Object, default: () => null },
	staff: { type: Object, default: () => null },
	address: { type: Object, default: () => null },
	contacts: { type: Array, default: () => null },
	qualifications: { type: Array, default: () => null },
	filters: { type: Object, default: () => null },
	permissions: { type: Object, default: () => null },
});

let BreadcrumbLinks = [
	{ name: "Staff", url: "/staff" },
	{ name: props.person.name, url: "/" },
];
const openEditContact = ref(false);
const toggleEditContactModal = useToggle(openEditContact);
// const confirmDelete = useToggle(openEditModal);

const editContactModal = () => {
	openEditContact.value = !openEditContact.value;
};
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
						class="mx-auto flex flex-wrap max-w-2xl items-center md:justify-between lg:justify-start gap-x-8 lg:mx-0 lg:max-w-none"
					>
						<div
							class="flex flex-wrap items-center justify-between md:justify-start gap-x-6 w-full md:w-1/2"
						>
							{{ person.image }}
							<Avatar
								:initials="person.initials"
								:image="person.image"
								size="lg"
							/>
							<div class="">
								<div class="text-sm leading-6 text-gray-500 dark:text-gray-300">
									File Number
									<span class="text-gray-700 dark:text-gray-100">{{
										staff.file_number
									}}</span>
								</div>
								<div class="text-sm leading-6 text-gray-500 dark:text-gray-300">
									Staff Number
									<span class="text-gray-700 dark:text-gray-100">{{
										staff.staff_number
									}}</span>
								</div>
								<div
									class="mt-1 text-xl font-semibold leading-6 text-gray-900 dark:text-white"
								>
									{{ person.name }}
								</div>
							</div>
						</div>
						<div>
							<div v-if="staff.statuses.length > 0" class="mt-4 md:mt-0">
								<div class="text-sm leading-6 text-gray-500 dark:text-gray-300">
									Current Status
									<span class="text-gray-700 dark:text-gray-100">{{
										staff.statuses[0]?.status_display
									}}</span>
								</div>
							</div>
							<div class="mt-4 md:mt-0">
								<div class="text-sm leading-6 text-gray-500 dark:text-gray-300">
									Current position
									<span class="text-gray-700 dark:text-gray-100">{{
										staff.positions[0]?.name
									}}</span>
								</div>
							</div>
						</div>
						<div
							class="flex items-center gap-x-4 sm:gap-x-6 justify-between w-full md:w-fit"
						>
							<button
								v-if="
									$page.props.permissions.includes('create staff promotion')
								"
								type="button"
								class="hidden text-sm font-semibold leading-6 text-green-900 dark:text-white sm:block"
								@click="togglePromotionForm()"
							>
								Promote
							</button>
							<button
								v-if="
									$page.props.permissions.includes('create staff transfers')
								"
								type="button"
								class="hidden text-sm font-semibold leading-6 text-green-900 dark:text-white sm:block"
								@click="toggleTransferForm()"
							>
								Transfer
							</button>
							<a
								v-if="$page.props.permissions.includes('update staff')"
								href="#"
								class="ml-auto flex items-center gap-x-1 rounded-md bg-green-600 dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600"
								@click.prevent="toggle()"
							>
								<PlusIcon class="-ml-1.5 h-5 w-5" aria-hidden="true" />
								Edit
							</a>
						</div>
					</div>
				</div>
			</header>

			<div class="mx-auto max-w-7xl py-4">
				<div
					class="mx-auto lg:grid max-w-2xl grid-cols-1 grid-rows-1 items-start lg:mx-0 px-4 lg:max-w-none lg:grid-cols-4 gap-4"
				>
					<div class="lg:col-start-4 flex flex-wrap gap-4 w-full">
						<!-- Employment summary -->
						<Summary :person="person" @open-edit-person="toggle()" />
						<!-- Contact information -->
						<StaffIdentities
							:identities="person.identities"
							:person="person.id"
							@edit-contact="toggleEditContactModal()"
						/>
						<Address
							:address="address"
							:contacts="contacts"
							:person="person.id"
							@edit-contact="toggleEditContactModal()"
						/>

						<!-- <StaffDependents v-if="staff" :staff="staff" class="" /> -->
					</div>

					<div
						class="col-start-1 col-span-3 lg:col-span-3 lg:row-span-3 lg:row-end-2 flex flex-wrap gap-4 items-start"
					>
						<div class="lg:flex flex-grow flex-wrap lg:gap-4 items-start">
							<!-- important Dates -->
							<StaffDates class="w-full xl:w-3/5" :staff="staff" />
							<div
								class="flex-1 flex xl:flex-col xl:gap-y-4 lg:flex-row lg:gap-x-4"
							>
								<StaffStatus
									:statuses="staff.statuses"
									:staff="{
										id: staff.staff_id,
										hire_date: staff.hire_date,
									}"
									:institution="staff.institution_id"
									class="flex-1"
									@close-form="toggleTransferForm()"
								/>
								<StaffType
									:types="staff.staff_type"
									:staff="{
										id: staff.staff_id,
										hire_date: staff.hire_date,
									}"
									:institution="staff.institution_id"
									class="flex-1"
									@close-form="toggleTransferForm()"
								/>
								<StaffPosition
									:positions="staff.positions"
									:staff="{
										id: staff.staff_id,
										hire_date: staff.hire_date,
									}"
									:institution="staff.institution_id"
									class="flex-1"
									@close-form="toggleTransferForm()"
								/>
							</div>
							<!-- statutes history -->
						</div>
						<!-- Qualifications -->
						<Qualifications
							class="w-full"
							:qualifications="qualifications"
							:person="{
								id: person.id,
								name: person.name,
							}"
						/>
						<!-- Qualifications -->
						<Notes
							class="w-full"
							:notes="staff.notes"
							notable_type="App\Models\InstitutionPerson"
							:notable_id="staff.staff_id"
							:user="user"
						/>
						<!-- Employment History -->
						<PromotionHistory
							:promotions="staff.ranks"
							:staff="staff.staff_id"
							:institution="staff.institution_id"
							:show-promotion-form="showPromotionForm"
							class="w-full xl:flex-1"
							@close-form="togglePromotionForm()"
						/>
						<!-- Posting History -->
						<TransferHistory
							:transfers="staff.units"
							:staff="staff.staff_id"
							:staff-name="person.name"
							:institution="staff.institution_id"
							:show-transfer-form="showTransferForm"
							class="w-full xl:flex-1"
							@close-form="toggleTransferForm()"
						/>
						<!-- TODO Add dependant forme and display -->
						<Dependents
							:staff-id="staff.staff_id"
							:dependents="staff.dependents"
						/>
					</div>
				</div>
			</div>
			<NewModal :show="openEditModal" @close="toggle()">
				<EditStaffForm :staff-id="staff.staff_id" @form-submitted="toggle()" />
			</NewModal>

			<NewModal :show="openEditContact" @close="toggleEditContactModal()">
				<!-- {{ contact }} -->
				<EditContactForm
					:contact="staff.staff_id"
					@form-submitted="(model) => editContactModal(model)"
				/>
			</NewModal>
		</main>
	</MainLayout>
</template>
