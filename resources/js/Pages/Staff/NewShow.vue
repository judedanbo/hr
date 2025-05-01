<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, usePage } from "@inertiajs/inertia-vue3";
import StaffDates from "./StaffDates.vue";
import Summary from "@/Pages/Person/Summary.vue";
import PromotionHistory from "./PromotionHistory.vue";
import TransferHistory from "./TransferHistory.vue";
import StaffStatus from "../StaffStatus/Index.vue";
import StaffType from "../StaffType/Index.vue";
import StaffPosition from "../StaffPosition/Index.vue";
import Qualifications from "./PersonQualifications.vue";
import Dependents from "@/Pages/StaffDependents/Index.vue";
import Roles from "@/Pages/Person/partials/Roles.vue";

// import Dependents from "./Dependents.vue";
import Address from "./Address.vue";
import StaffIdentities from "./StaffIdentities.vue";
import Notes from "./Notes.vue";
import { useToggle } from "@vueuse/core";
import { ref, computed } from "vue";
import { PencilIcon } from "@heroicons/vue/20/solid";
import Avatar from "../Person/partials/Avatar.vue";
import NewModal from "@/Components/NewModal.vue";
import EditStaffForm from "./EditStaffForm.vue";
import EditContactForm from "./EditContactForm.vue";
import EditAvatarForm from "./EditAvatarForm.vue";
import DeleteAvatar from "./DeleteAvatar.vue";
import { Inertia } from "@inertiajs/inertia";

let showPromotionForm = ref(false);
let showTransferForm = ref(false);
let openEditModal = ref(false);

const deleteAvatar = () => {
	Inertia.delete(
		route("person.avatar.delete", {
			person: props.person.id,
		}),
		{
			preserveScroll: true,
			onSuccess: () => {
				toggleDeleteAvatarModal();
			},
		},
	);
};

let toggle = useToggle(openEditModal);

const showAvatarModel = ref(false);
const toggleAvatarModal = useToggle(showAvatarModel);

const page = usePage();
const permissions = computed(() => page.props.value.auth.permissions);

const openDeleteAvatarModel = ref(false);
const toggleDeleteAvatarModal = useToggle(openDeleteAvatarModel);

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
	// permissions: { type: Object, default: () => null },
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

				<div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
					<div
						class="mx-auto flex flex-wrap max-w-2xl items-center justify-center lg:justify-start gap-x-8 lg:mx-0 lg:max-w-none"
					>
						<div
							class="flex flex-wrap flex-col sm:flex-row items-center justify-center lg:justify-start gap-x-6 md:w-full"
						>
							<!-- {{ person.image }} -->
							<Avatar
								@change-avatar="toggleAvatarModal()"
								:initials="person.initials"
								:image="person.image"
								size="lg"
							/>
							<div class="flex flex-col">
								<div>
									<div
										class="mt-2 text-xl text-center font-semibold leading-6 text-gray-900 dark:text-white"
									>
										{{ person.name }}
									</div>
									<div class="flex justify-between mt-2">
										<Roles :person="person.id" class="" />
										<div
											class="text-sm leading-6 text-gray-500 dark:text-gray-300 flex gap-4 justify-center"
										>
											<!-- File Number -->
											<div class="">
												{{ staff.file_number }}
											</div>
											<div class="">
												{{ staff.staff_number }}
											</div>
										</div>
									</div>
									<div class="mt-4 md:mt-0">
										<div
											class="text-sm leading-6 text-gray-500 dark:text-gray-300"
										>
											<span class="text-gray-700 dark:text-gray-100">{{
												staff.positions[0]?.name
											}}</span>
										</div>
									</div>
									<div
										@click="toggleDeleteAvatarModal()"
										v-if="person.image"
										class="mt-2 cursor-pointer text=sm leading-6 text-red-500 dark:text-red-400"
									>
										Delete Image
									</div>
								</div>

								<div
									class="flex items-center gap-x-4 sm:gap-x-6 justify-between w-full md:w-fit mt-0 md:mt-4 lg:mt-0"
								>
									<button
										v-if="permissions.includes('create staff promotion')"
										type="button"
										class="text-sm font-semibold leading-6 text-green-900 dark:text-white sm:block"
										@click="togglePromotionForm()"
									>
										Promote
									</button>
									<button
										v-if="permissions.includes('create staff transfers')"
										type="button"
										class="text-sm font-semibold leading-6 text-green-900 dark:text-white sm:block"
										@click="toggleTransferForm()"
									>
										Transfer
									</button>
									<a
										v-if="permissions.includes('update staff')"
										href="#"
										class="ml-auto flex items-center gap-x-1 rounded-md bg-green-600 dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600"
										@click.prevent="toggle()"
									>
										<PencilIcon class="-ml-1.5 h-5 w-5" aria-hidden="true" />
										Edit
									</a>
								</div>
							</div>
						</div>
						<!-- <div> -->
						<div
							v-if="staff.statuses.length > 0"
							class="flex justify-end mt-1 md:mt-0 w-full"
						></div>

						<!-- </div> -->
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
							@delete-contact="editContactModal()"
						/>

						<!-- <StaffDependents v-if="staff" :staff="staff" class="" /> -->
					</div>

					<div
						class="col-start-1 col-span-3 lg:col-span-3 lg:row-span-3 lg:row-end-2 flex flex-wrap gap-4 items-start"
					>
						<div class="lg:flex flex-grow flex-wrap lg:gap-4 items-start">
							<!-- important Dates -->
							<StaffDates class="w-full xl:w-3/5 mt-4" :staff="staff" />
							<div
								class="flex-1 flex flex-col xl:flex-col gap-y-4 flex-shrink-0 lg:flex-row lg:gap-x-4 flex-wrap mt-4"
							>
								<StaffStatus
									:statuses="staff.statuses"
									:staff="{
										id: staff.staff_id,
										hire_date: staff.hire_date,
									}"
									:institution="staff.institution_id"
									class=""
									@close-form="toggleTransferForm()"
								/>
								<StaffType
									:types="staff.staff_type"
									:staff="{
										id: staff.staff_id,
										hire_date: staff.hire_date,
									}"
									:institution="staff.institution_id"
									class=""
									@close-form="toggleTransferForm()"
								/>
								<StaffPosition
									v-if="permissions.includes('update staff positions')"
									:positions="staff.positions"
									:staff="{
										id: staff.staff_id,
										hire_date: staff.hire_date,
									}"
									:institution="staff.institution_id"
									class=""
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
							class="w-full xl:w-2/3"
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
			<NewModal :show="showAvatarModel" @close="toggleAvatarModal()">
				<!-- {{ staff }} -->
				<EditAvatarForm
					:avatar="person.image"
					:staff="{
						id: staff.staff_id,
						name: person.name,
						image: person.image,
						person_id: person.id,
					}"
					@imageUpdated="toggleAvatarModal()"
				/>
			</NewModal>
			<NewModal
				:show="openDeleteAvatarModel"
				@close="toggleDeleteAvatarModal()"
			>
				<DeleteAvatar
					@delete-confirmed="deleteAvatar()"
					@close="toggleDeleteAvatarModal()"
				/>
			</NewModal>
		</main>
	</MainLayout>
</template>
