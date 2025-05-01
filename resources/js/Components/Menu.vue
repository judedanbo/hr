<script setup>
import {
	Squares2X2Icon,
	UsersIcon,
	Cog8ToothIcon,
	ChevronUpIcon,
	ChevronDownIcon,
	DocumentChartBarIcon,
	PaperAirplaneIcon,
	AdjustmentsHorizontalIcon,
	BellIcon,
	UserGroupIcon,
	RectangleGroupIcon,
	RectangleStackIcon,
} from "@heroicons/vue/24/outline";
import { computed, ref } from "vue";
import { usePage } from "@inertiajs/inertia-vue3";
import MainMenu from "@/Components/MainMenu.vue";
import VMenu from "@/Components/VMenu.vue";

const isOpen = ref(false);

const page = usePage();
const user = computed(() => page.props.value.auth.user);
</script>
<template>
	<div
		class="xl:rounded-r transform xl:translate-x-0 ease-in-out transition duration-500 hidden md:flex justify-start items-start h-full bg-green-700 flex-col w-11 lg:w-48"
	>
		<div
			class="mt-6 flex flex-col justify-start items-center w-full border-gray-50 border-b pb-5"
		>
			<MainMenu
				:active="route().current('institution.show', { institution: 1 })"
				name="Dashboard"
				:href="route('dashboard')"
			>
				<template #icon>
					<Squares2X2Icon class="w-6"></Squares2X2Icon>
				</template>
			</MainMenu>
			<!-- <MainMenu
                :active="route().current('person.index')"
                name="Users"
                :href="route('person.index')"
            >
                <template #icon>
                    <UsersIcon class="w-6"></UsersIcon>
                </template>
            </MainMenu> -->
			<MainMenu
				:active="route().current('report.*')"
				name="Reports"
				:href="route('report.index')"
			>
				<template #icon>
					<DocumentChartBarIcon class="w-6"></DocumentChartBarIcon>
				</template>
			</MainMenu>
		</div>
		<!-- <MainMenu
            name="Personal Profile"
            :active="route().current('dashboard')"
        >
            <VMenu
                :active="route().current('person.show', { person: 1 })"
                name="Messages"
                :href="route('person.index')"
            >
                <template #icon>
                    <PaperAirplaneIcon
                        class="w-5 -rotate-45"
                    ></PaperAirplaneIcon>
                </template>
                >
            </VMenu>
            <VMenu
                :active="route().current('person.show', { person: 1 })"
                name="Settings"
                :href="route('person.index')"
            >
                <template #icon>
                    <AdjustmentsHorizontalIcon class="w-5" />
                </template>
                />
            </VMenu>
            <VMenu
                :active="route().current('person.show', { person: 1 })"
                name="Notifications"
                :href="route('person.index')"
            >
                <template #icon> <BellIcon class="w-5 -rotate-45" /></template>
                />
            </VMenu>
        </MainMenu> -->
		<MainMenu
			:active="route().current('staff.index')"
			name="Staff"
			:href="route('staff.index')"
		>
			<template #icon> <UserGroupIcon class="w-6" /> </template>
		</MainMenu>
		<MainMenu
			:active="route().current('unit.index')"
			name="Departments"
			:href="route('unit.index')"
			:visible="false"
		>
			<template #icon> <RectangleGroupIcon class="w-6" /></template
		></MainMenu>
		<MainMenu
			:active="route().current('job.index')"
			name="Rank"
			:href="route('job.index')"
		>
			<template #icon> <RectangleStackIcon class="w-6" /></template
		></MainMenu>

		<div
			class="flex flex-col justify-between items-center h-full pb-6 px-1 lg:px-6 mt-48"
		>
			<div class="flex justify-between items-center w-full">
				<div class="flex justify-center items-center space-x-2">
					<div class="rounded-full w-10 h-10 bg-gray-200"></div>
					<div class="hidden lg:flex justify-start flex-col items-start">
						<p class="cursor-pointer text-sm leading-5 text-white">
							{{ user.name }}
						</p>
						<p class="cursor-pointer text-xs leading-3 text-gray-300">
							{{ user.email }}
						</p>
					</div>
				</div>
				<Cog8ToothIcon class="hidden lg:flex cursor-pointer w-6 text-white" />
			</div>
		</div>
	</div>
</template>
