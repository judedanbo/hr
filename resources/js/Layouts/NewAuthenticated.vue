<script setup>
import { ref, onMounted, onUpdated, computed } from "vue";
import MainNav from "../Components/MainNav.vue";
import NewNav from "../Components/NewNav.vue";
import TopMenu from "@/Components/TopMenu.vue";
import { Link, usePage } from "@inertiajs/vue3";
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
	UserGroupIcon,
	ShieldCheckIcon,
	PhotoIcon,
} from "@heroicons/vue/24/outline";
import BreezeApplicationLogo from "@/Components/ApplicationLogo.vue";

const page = usePage();
const permissions = computed(() => page.props?.auth?.permissions);
const alert = computed(() => page.props?.flash);
const leavePlanning = computed(() => page.props?.leavePlanning);
const navigation = [
	{
		name: "My Profile",
		href: route("my-profile.show"),
		icon: UserGroupIcon,
		current: route().current("my-profile.*"),
		visible: Boolean(page.props?.auth?.user?.person_id),
	},
	{
		name: "Dashboard",
		href: route("dashboard"),
		icon: HomeIcon,
		current: route().current("institution.show"),
		visible: permissions.value?.includes("view dashboard"),
	},
	{
		name: "Staff",
		href: route("staff.index"),
		icon: UsersIcon,
		current: route().current("staff.*"),
		visible:
			permissions.value?.includes("view all staff") ||
			permissions.value?.includes("view staff"),
	},
	{
		name: "Separations",
		href: route("separation.index"),
		icon: UsersIcon,
		current: route().current("separation.*"),
		visible: permissions.value?.includes("view all separations"),
	},
	{
		name: "Departments",
		href: route("unit.index"),
		icon: FolderIcon,
		current: route().current("unit.*"),
		visible: permissions.value?.includes("view all units"),
	},
	{
		name: "Ranks",
		href: route("job.index"),
		icon: CalendarIcon,
		current: route().current("job.*") || route().current("job.*"),
		visible: permissions.value?.includes("view all jobs"),
	},
	{
		name: "Harmonized Grades",
		href: route("job-category.index"),
		icon: CalendarIcon,
		current:
			route().current("job-category.*") || route().current("job-category.*"),
		visible: permissions.value?.includes("view job category"),
	},
	{
		name: "Leave Setup",
		href: route("leave-year.index"),
		icon: CalendarIcon,
		current:
			route().current("leave-year.*") ||
			route().current("leave-type.*") ||
			route().current("leave-entitlement.*") ||
			route().current("holiday.*") ||
			route().current("leave-planning-window.*") ||
			route().current("unit-head.*") ||
			route().current("leave-delegation.*"),
		children: [
			{
				name: "Leave Years",
				href: route("leave-year.index"),
				current: route().current("leave-year.*"),
				visible: permissions.value?.includes("view all leave years"),
			},
			{
				name: "Leave Types",
				href: route("leave-type.index"),
				current: route().current("leave-type.*"),
				visible: permissions.value?.includes("view all leave types"),
			},
			{
				name: "Entitlements",
				href: route("leave-entitlement.index"),
				current: route().current("leave-entitlement.*"),
				visible: permissions.value?.includes("view all leave entitlements"),
			},
			{
				name: "Holidays",
				href: route("holiday.index"),
				current: route().current("holiday.*"),
				visible: permissions.value?.includes("view all holidays"),
			},
			{
				name: "Planning Windows",
				href: route("leave-planning-window.index"),
				current: route().current("leave-planning-window.*"),
				visible: permissions.value?.includes("manage leave planning windows"),
			},
			{
				name: "Unit Heads",
				href: route("unit-head.index"),
				current: route().current("unit-head.*"),
				visible: permissions.value?.includes("manage leave approvers"),
			},
			{
				name: "Delegations",
				href: route("leave-delegation.index"),
				current: route().current("leave-delegation.*"),
				visible: permissions.value?.includes("manage leave delegations"),
			},
		],
		visible:
			permissions.value?.includes("view all leave years") ||
			permissions.value?.includes("view all leave types") ||
			permissions.value?.includes("view all leave entitlements") ||
			permissions.value?.includes("view all holidays") ||
			permissions.value?.includes("manage leave planning windows") ||
			permissions.value?.includes("manage leave approvers") ||
			permissions.value?.includes("manage leave delegations"),
	},
	{
		name: "My Leave Plan",
		href: route("leave-plan.index"),
		icon: CalendarIcon,
		current: route().current("leave-plan.*"),
		visible: permissions.value?.includes("view leave plans"),
	},
	{
		name: "All Leave Plans",
		href: route("leave-plans.index"),
		icon: DocumentDuplicateIcon,
		current: route().current("leave-plans.*"),
		visible: permissions.value?.includes("view all leave plans"),
	},
	{
		name: "My Leave",
		href: route("leave-request.index"),
		icon: CalendarIcon,
		current: route().current("leave-request.*"),
		visible: permissions.value?.includes("view leave requests"),
	},
	{
		name: "All Leave Requests",
		href: route("leave-requests.index"),
		icon: DocumentDuplicateIcon,
		current: route().current("leave-requests.*"),
		visible: permissions.value?.includes("view all leave requests"),
	},
	{
		name: "Leave Approvals",
		href: route("leave-approvals.index"),
		icon: DocumentDuplicateIcon,
		current: route().current("leave-approvals.*"),
		visible: Boolean(page.props?.auth?.user?.person_id),
	},
	{
		name: "Leave Balance",
		href: route("leave-balance.index"),
		icon: CalendarIcon,
		current: route().current("leave-balance.*"),
		visible: permissions.value?.includes("view leave requests"),
	},
	{
		name: "Leave Calendar",
		href: route("leave-calendar.index"),
		icon: CalendarIcon,
		current: route().current("leave-calendar.*"),
		visible: permissions.value?.includes("view leave calendar"),
	},
	{
		name: "Next Promotions",
		href: route("promotion.batch.index"),
		icon: DocumentDuplicateIcon,
		current: route().current("promotion.batch.show"),
		visible: permissions.value?.includes("view all staff promotions"),
	},
	{
		name: "Past Promotions",
		href: route("promotion.index"),
		icon: DocumentDuplicateIcon,
		current: route().current("promotion.index"),
		visible: permissions.value?.includes("view all past promotions"),
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
			{
				name: "Qualifications",
				href: route("qualifications.reports.index"),
				current: route().current("qualifications.reports.*"),
				visible: permissions.value?.includes("qualifications.reports.view"),
			},
		],
		visible:
			permissions.value?.includes("view all reports") ||
			permissions.value?.includes("qualifications.reports.view"),
	},
	{
		name: "Users",
		href: route("user.index"),
		icon: UsersIcon,
		current: route().current("user.*"),
		visible: permissions.value?.includes("view all users"),
	},
	{
		name: "Roles",
		href: route("role.index"),
		icon: UserGroupIcon,
		current: route().current("role.*"),
		visible: permissions.value?.includes("view all roles"),
	},
	{
		name: "Audit Logs",
		href: route("audit-log.index"),
		icon: UsersIcon,
		current: route().current("logs.*"),
		visible: permissions.value?.includes("view all audit logs"),
	},
	{
		name: "Photo Approvals",
		href: route("photo-approvals.index"),
		icon: PhotoIcon,
		current: route().current("photo-approvals.*"),
		visible: permissions.value?.includes("approve staff photo"),
	},
	{
		name: "Data Integrity",
		href: route("data-integrity.index"),
		icon: ShieldCheckIcon,
		current: route().current("data-integrity.*"),
		visible: page.props?.auth?.roles?.includes("super-administrator"),
	},
];
const userNavigation = [
	// { name: "Your profile", href: "#" },
	{ name: "Change password", href: route("change-password.index") },
	{ name: "Sign out", href: route("logout"), method: "post" },
];

const sidebarOpen = ref(false);

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

				<TopMenu :user-navigation="userNavigation" />
			</div>

			<main class="pb-6 bg-gray-100 dark:bg-gray-600 min-h-screen">
				<div
					v-if="leavePlanning?.open && !leavePlanning?.submitted"
					class="bg-amber-50 border-b border-amber-200 px-6 py-3 text-sm text-amber-900"
				>
					<span class="font-semibold">Leave planning is open.</span>
					Submit your leave plan by {{ leavePlanning.closes_at }}.
					<Link
						:href="route('leave-plan.index')"
						class="font-semibold underline hover:text-amber-700"
					>
						Go to My Leave Plan
					</Link>
				</div>
				<div class="">
					<!-- permissions: {{ permissions }} -->
					<slot />
				</div>
			</main>
		</div>
	</div>
	<div class="fixed bottom-10 right-5 flex flex-col space-y-3">
		<template v-for="(alertItem, key, index) in alert" :key="index">
			<Alert
				v-if="alertItem"
				:alert="alertItem"
				:type="key"
				:index="index"
				@close="closeAlert(index)"
			/>
		</template>
	</div>
</template>
