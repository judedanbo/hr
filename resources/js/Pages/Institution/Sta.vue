<script setup>
import MainLayout from "@/Layouts/HrAuthenticated.vue";
import { Head, Link } from "@inertiajs/vue3";
import Tab from "@/Components/Tab.vue";
import { format, differenceInYears } from "date-fns";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import StaffPersonalInfo from "@/Components/StaffPersonalInfo.vue";
import StaffDependents from "@/Components/StaffDependents.vue";
import PersonContacts from "../Person/PersonContacts.vue";
import PersonAddresses from "../Person/PersonAddresses.vue";
import StaffJobsVue from "../Staff/StaffJobs.vue";
import {
	BriefcaseIcon,
	BuildingOffice2Icon,
	MagnifyingGlassIcon,
	ChevronLeftIcon,
	ChevronRightIcon,
	EnvelopeIcon,
	PaperClipIcon,
} from "@heroicons/vue/24/outline";

// import { PaperClipIcon } from '@heroicons/vue/20/solid'

const formattedDob = (dateString) => {
	const date = new Date(dateString);
	return format(date, "dd MMMM, yyyy");
};

let getAge = (dateString) => {
	const date = new Date(dateString);
	return differenceInYears(new Date(), date);
};

let props = defineProps({
	// staff: Object,
	person: Object,
	contacts: Object,
	contact_types: Object,
	institution: Object,
	staff: Object,
	filters: Object,
});

let tabs = [
	{ name: "Profile", active: true },
	{ name: "Account" },
	{ name: "Notification" },
];
let BreadcrumbLinks = [
	{ name: "People", url: "/staff" },
	{ name: props.staff.initials, url: "/" },
];
</script>

<template>
	<Head :title="staff.name + ' - ' + institution.name" />

	<MainLayout>
		<template #header>
			<BreadCrumpVue :links="BreadcrumbLinks" />
			<h2 class="font-semibold text-xl text-gray-800 leading-tight">
				{{ staff.name }}
			</h2>
		</template>

		<div class="py-12">
			<div class="max-w-7xl mx-auto md:px-6 lg:px-8">
				<div class="bg-white shadow-sm md:rounded-lg">
					<div
						class="px-4 md:px-0 bg-white border-b border-gray-200 md:flex justify-around md:justify-start mt-14 md:mt-0"
					>
						<div
							class="flex flex-col md:flex-row items-center justify-center \"
						>
							<div
								class="w-48 h-48 md:w-80 md:h-full rounded-full md:rounded-none bg-gray-400 flex justify-center items-center -mt-24 md:mt-0"
							>
								<h1
									class="text-white font-semibold text-6xl md:text-7xl tracking-widest"
								>
									{{ person.initials }}
								</h1>
							</div>
							<div class="pt-8 w-full md:p-8">
								<h1
									class="text-2xl md:text-3xl lg:text-4xl font-bold tracking-wider text-gray-700"
								>
									{{ person.name }}
								</h1>

								<p class="text-sm md:text-lg font-bold">
									{{ staff.unit.name }}
								</p>
								<p class="text-lg md:text-xl text-gray-500 py-4">
									{{ staff.current_job }}
								</p>
								<div class="lg:flex space-y-8 lg:space-y-0 justify-between">
									<div>
										<p class="text-lg md:text-xl">
											Gender.: {{ person.gender }}
										</p>
										<p
											class="text-lg md:text-xl"
											:title="getAge(person.dob) + ' years old'"
										>
											Born:
											{{ formattedDob(person.dob) }}
										</p>
									</div>
									<div>
										<p
											class="text-lg md:text-xl"
											:title="getAge(staff.hire_date) + ' years employed'"
										>
											Employed:
											{{ formattedDob(staff.hire_date) }}
										</p>
										<p class="text-lg md:text-xl">
											Staff No.: {{ staff.staff_number }}
										</p>
									</div>
								</div>

								<Link
									as="button"
									:href="'mailto:' + staff.email"
									:title="staff.email"
									class="mt-8 py-2.5 px-5 mr-2 text-sm md:text-lg font-medium text-gray-900 bg-white rounded-lg border border-green-50 hover:bg-gray-100 hover:text-green-700 focus:z-10 focus:ring-1 focus:outline-none focus:ring-green-700 focus:text-green-700 dark:bg-gray-800 dark:text-gray-400 dark:border-green-600 dark:hover:text-white dark:hover:bg-gray-700 inline-flex items-center"
								>
									<EnvelopeIcon class="w-5 h-5 mr-2" />
									Email
								</Link>

								<div class="flex items-center justify-around">
									<!-- <p
                                        class="text-lg"
                                        :title="
                                            'born ' + formattedDob(staff.dob)
                                        "
                                    >
                                        Age: {{ getAge(staff.dob) }} years
                                    </p> -->
									<!-- <p
                                        class="text-lg"
                                        :title="
                                            getAge(staff.hire_date) +
                                            ' years employed'
                                        "
                                    >
                                        Employed:
                                        {{ formattedDob(staff.hire_date) }}
                                    </p> -->
								</div>

								<!-- <p class="text-lg tracking-wide">
                                    SSNIT No. {{ staff.ssn }}
                                </p> -->
							</div>
						</div>
					</div>
				</div>
				<div
					class="flex mt-8 flex-wrap justify-center md:justify-between gap-4 items-start"
				>
					<StaffPersonalInfo class="w-2/5" :person="person" />
					<StaffJobsVue class="w-2/5" :jobs="staff.jobs" />
					<div class="w-2/5">
						<PersonContacts
							:person="person"
							:contacts="contacts"
							:types="contact_types"
						/>
						<PersonAddresses :person="person" class="mt-4" />
					</div>
					<StaffDependents :staff="staff" :person="person" class="w-2/5" />
				</div>
			</div>
		</div>
	</MainLayout>
</template>
