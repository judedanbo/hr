<script setup>
import { ref, onMounted, onUpdated } from "vue";
import MainNav from "../Components/MainNav.vue";
import NewNav from "../Components/NewNav.vue";
import TopMenu from "@/Components/TopMenu.vue";
import { Link, usePage } from "@inertiajs/inertia-vue3";
import Alert from "@/Components/Alert.vue";
import { useToggle } from "@vueuse/core";
import {
	Dialog,
	DialogPanel,
	TransitionChild,
	TransitionRoot,
} from "@headlessui/vue";
import {
	Bars3Icon,
	CalendarIcon,
	ChartPieIcon,
	DocumentDuplicateIcon,
	FolderIcon,
	HomeIcon,
	UsersIcon,
	XMarkIcon,
} from "@heroicons/vue/24/outline";
import BreezeApplicationLogo from "@/Components/ApplicationLogo.vue";

const navigation = [
	{
		name: "Dashboard",
		href: route("dashboard"),
		icon: HomeIcon,
		current: route().current("institution.show"),
	},
	{
		name: "Staff",
		href: route("staff.index"),
		icon: UsersIcon,
		current: route().current("staff.*"),
	},
	{
		name: "Separations",
		href: route("separation.index"),
		icon: UsersIcon,
		current: route().current("separation.*"),
	},
	{
		name: "Departments",
		href: route("unit.index"),
		icon: FolderIcon,
		current: route().current("unit.*"),
	},
	{
		name: "Ranks",
		href: route("job.index"),
		icon: CalendarIcon,
		current: route().current("job.*") || route().current("job.*"),
	},
	{
		name: "Harmonized Grades",
		href: route("job-category.index"),
		icon: CalendarIcon,
		current:
			route().current("job-category.*") || route().current("job-category.*"),
	},
	{
		name: "Next Promotions",
		href: route("promotion.batch.index"),
		icon: DocumentDuplicateIcon,
		current: route().current("promotion.batch.show"),
	},
	{
		name: "Past Promotions",
		href: route("promotion.index"),
		icon: DocumentDuplicateIcon,
		current: route().current("promotion.index"),
	},
	{
		name: "Reports",
		href: route("report.index"),
		icon: ChartPieIcon,
		current: route().current("report.*"),
		children: [
			{
				name: "Staff position",
				href: route("report.staff"),
				current: route().current("report.promotion"),
			},
			{
				name: "Promotion Report",
				// href: route("report.promotion"),
				current: route().current("report.promotion"),
			},
			{
				name: "Staff Report",
				// href: route("report.staff"),
				current: route().current("report.staff"),
			},
			{
				name: "Separation Report",
				// href: route("report.separation"),
				current: route().current("report.separation"),
			},
			{
				name: "Unit Report",
				// href: route("report.unit"),
				current: route().current("report.unit"),
			},
		],
	},
];
const teams = [
	{ id: 1, name: "Heroicons", href: "#", initial: "H", current: false },
	{ id: 2, name: "Tailwind Labs", href: "#", initial: "T", current: false },
	{ id: 3, name: "Workcation", href: "#", initial: "W", current: false },
];
const userNavigation = [
	{ name: "Your profile", href: "#" },
	{ name: "Sign out", href: route("logout") },
];

const sidebarOpen = ref(false);
//  flash message
const alertOpen = ref(true);
const toggleAlert = useToggle(alertOpen);
const alert = ref(null);
// onMounted(() => {
//     alert.value = usePage().props.value.flash;
//     // if (alert) {
//     //     toggleAlert();
//     //     setTimeout(() => {
//     //         toggleAlert();
//     //     }, 3000);
//     // }
// });

onUpdated(() => {
	alert.value = usePage().props.value.flash;
	// setTimeout(() => {
	//     alert.value = null;
	// }, 3000);
	// if (alert.success !== null) {
	//     toggleAlert();
	// }
});
const closeAlert = (index) => {
	delete alert.value[Object.keys(alert.value)[index]];
};
</script>
<template>
	<div>
		<TransitionRoot as="template" :show="sidebarOpen">
			<Dialog
				as="div"
				class="relative z-50 lg:hidden"
				@close="sidebarOpen = false"
			>
				<TransitionChild
					as="template"
					enter="transition-opacity ease-linear duration-300"
					enter-from="opacity-0"
					enter-to="opacity-100"
					leave="transition-opacity ease-linear duration-300"
					leave-from="opacity-100"
					leave-to="opacity-0"
				>
					<div class="fixed inset-0 bg-gray-900/80" />
				</TransitionChild>

				<div class="fixed inset-0 flex">
					<TransitionChild
						as="template"
						enter="transition ease-in-out duration-300 transform"
						enter-from="-translate-x-full"
						enter-to="translate-x-0"
						leave="transition ease-in-out duration-300 transform"
						leave-from="translate-x-0"
						leave-to="-translate-x-full"
					>
						<DialogPanel class="relative mr-16 flex w-full max-w-xs flex-1">
							<TransitionChild
								as="template"
								enter="ease-in-out duration-300"
								enter-from="opacity-0"
								enter-to="opacity-100"
								leave="ease-in-out duration-300"
								leave-from="opacity-100"
								leave-to="opacity-0"
							>
								<div
									class="absolute left-full top-0 flex w-16 justify-center pt-5"
								>
									<button
										type="button"
										class="-m-2.5 p-2.5"
										@click="sidebarOpen = false"
									>
										<span class="sr-only">Close sidebar</span>
										<XMarkIcon class="h-6 w-6 text-white" aria-hidden="true" />
									</button>
								</div>
							</TransitionChild>
							<!-- Sidebar component, swap this element with another sidebar if you like -->
							<div
								class="flex grow flex-col gap-y-5 overflow-y-auto bg-white px-6 pb-4 dark:bg-gray-800"
							>
								<div class="flex h-16 shrink-0 items-center">
									<BreezeApplicationLogo class="block h-9 w-auto" />
								</div>
								<MainNav :navigation="navigation" />
								<!-- <NewNav :navigation="navigation" /> -->
							</div>
						</DialogPanel>
					</TransitionChild>
				</div>
			</Dialog>
		</TransitionRoot>

		<!-- Static sidebar for desktop -->
		<div
			class="hidden lg:fixed lg:inset-y-0 lg:z-50 lg:flex lg:w-56 xl:w-72 lg:flex-col"
		>
			<!-- Sidebar component, swap this element with another sidebar if you like -->
			<div
				class="flex grow flex-col gap-y-5 overflow-y-auto border-r border-gray-200 bg-white dark:border-gray-900 dark:bg-gray-800"
			>
				<Link
					:href="route('dashboard')"
					class="flex h-16 shrink-0 items-center pl-2"
				>
					<BreezeApplicationLogo class="block h-9 w-auto" />
					<div class="mx-2">
						<h2
							class="font-semibold text-xl text-gray-800 leading-tight dark:text-gray-50"
						>
							Audit Service
						</h2>
					</div>
				</Link>
				<NewNav :navigation="navigation" />
				<!-- <MainNav :navigation="navigation" :teams="teams" /> -->
			</div>
		</div>

		<div class="lg:pl-56 xl:pl-72">
			<div
				class="sticky top-0 z-40 flex h-16 shrink-0 items-center gap-x-4 border-b border-gray-200 bg-white px-4 shadow-sm sm:gap-x-6 sm:px-6 lg:px-8 dark:bg-gray-800 dark:border-gray-700"
			>
				<button
					type="button"
					class="-m-2.5 p-2.5 text-gray-700 dark:text-gray-50 lg:hidden"
					@click="sidebarOpen = true"
				>
					<span class="sr-only">Open sidebar</span>
					<Bars3Icon class="h-6 w-6" aria-hidden="true" />
				</button>

				<!-- Separator -->
				<div class="h-6 w-px bg-gray-200 lg:hidden" aria-hidden="true" />

				<TopMenu :userNavigation="userNavigation" />
			</div>

			<main class="pb-6 bg-gray-100 dark:bg-gray-600 min-h-screen">
				<div class="">
					<slot />
				</div>
			</main>
		</div>
	</div>
	<div class="fixed bottom-10 right-5 flex flex-col space-y-3">
		<template v-for="(alertItem, key, index) in alert" :key="index">
			<Alert
				@close="closeAlert(index)"
				v-if="alertItem"
				:alert="alertItem"
				:type="key"
				:index="index"
			/>
		</template>
	</div>
</template>
