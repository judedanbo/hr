<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, Link } from "@inertiajs/inertia-vue3";
import Tab from "@/Components/Tab.vue";
import { Inertia } from "@inertiajs/inertia";
import { MagnifyingGlassIcon, PlusIcon } from "@heroicons/vue/24/outline";

import { format, differenceInYears } from "date-fns";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import BreezeInput from "@/Components/Input.vue";
import { ref, watch } from "vue";
import debounce from "lodash/debounce";
import InfoCard from "@/Components/InfoCard.vue";
import Avatar from "../Person/partials/Avatar.vue";
import { Menu, MenuButton, MenuItem, MenuItems } from "@headlessui/vue";
import { EllipsisVerticalIcon } from "@heroicons/vue/20/solid";
let props = defineProps({
	job: Object,

	filters: Object,
});
let search = ref(props.filters.search);
watch(
	search,
	debounce(function (value) {
		Inertia.get(
			route("job.show", {
				job: props.job.id,
			}),
			{ search: value },
			{ preserveState: true, replace: true, preserveScroll: true },
		);
	}, 300),
);
</script>
<template>
	<Head :title="job.name" />
	<MainLayout>
		<div class="overflow-hidden shadow-sm sm:rounded-lg">
			<div class="px-6 border-b border-gray-200">
				<div class="sm:flex items-center justify-between my-2">
					<FormKit
						v-model="search"
						prefix-icon="search"
						type="search"
						placeholder="Search ranks..."
						autofocus
					/>
					<InfoCard :title="job.name" :value="job.staff_count" link="#" />
				</div>
				<ul
					role="list"
					class="divide-y divide-gray-100n dark:divide-gray-400 px-8 bg-white dark:bg-gray-500 w-3/4 mx-auto"
				>
					<li
						v-for="staff in job.staff"
						:key="staff.id"
						class="flex justify-between gap-x-6 py-2.5"
					>
						<div class="flex min-w-0 gap-x-4">
							<Avatar :initials="staff.initials" :image="staff.image" size="sm" />
							<div class="min-w-0 flex-auto">
								<p
									class="text-sm font-semibold leading-6 text-gray-900 dark:text-gray-50"
								>
									<a :href="staff.href" class="hover:underline">{{
										staff.name
									}}</a>
								</p>
								<p
									class="mt-1 flex text-xs leading-5 text-gray-500 dark:text-gray-300"
								>
									<a
										:href="`mailto:${staff.email}`"
										class="truncate hover:underline"
										>{{ staff.staff_number }} | {{ staff.file_number }}</a
									>
								</p>
							</div>
						</div>
						<div class="flex shrink-0 items-center gap-x-6">
							<div class="hidden sm:flex sm:flex-col sm:items-end">
								<p class="text-sm leading-6 text-gray-900 dark:text-gray-50">
									{{ staff.rank }}
								</p>
								<p
									v-if="staff.rank_start"
									class="mt-1 text-xs leading-5 text-gray-500 dark:text-gray-300"
								>
									Since
									<time :datetime="staff.rank_start">{{
										staff.rank_start_text
									}}</time>
								</p>
								<div v-else class="mt-1 flex items-center gap-x-1.5">
									<div class="flex-none rounded-full bg-emerald-500/20 p-1">
										<div class="h-1.5 w-1.5 rounded-full bg-emerald-500" />
									</div>
									<p class="text-xs leading-5 text-gray-500 dark:text-gray-300">
										Online
									</p>
								</div>
							</div>
							<Menu as="div" class="relative flex-none">
								<MenuButton
									class="-m-2.5 block p-2.5 text-gray-500 hover:text-gray-900 dark:text-gray-50"
								>
									<span class="sr-only">Open options</span>
									<EllipsisVerticalIcon class="h-5 w-5" aria-hidden="true" />
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
										class="absolute right-0 z-10 mt-2 w-32 origin-top-right rounded-md bg-white py-2 shadow-lg ring-1 ring-gray-900/5 focus:outline-none"
									>
										<MenuItem v-slot="{ active }">
											<a
												href="#"
												:class="[
													active ? 'bg-gray-50' : '',
													'block px-3 py-1 text-sm leading-6 text-gray-900 dark:text-gray-50',
												]"
												>View profile<span class="sr-only"
													>, {{ staff.name }}</span
												></a
											>
										</MenuItem>
										<MenuItem v-slot="{ active }">
											<a
												href="#"
												:class="[
													active ? 'bg-gray-50' : '',
													'block px-3 py-1 text-sm leading-6 text-gray-900 dark:text-gray-50',
												]"
												>Message<span class="sr-only"
													>, {{ staff.name }}</span
												></a
											>
										</MenuItem>
									</MenuItems>
								</transition>
							</Menu>
						</div>
					</li>
				</ul>
			</div>
		</div>
	</MainLayout>
</template>
