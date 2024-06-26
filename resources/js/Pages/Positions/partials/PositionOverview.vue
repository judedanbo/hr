<template>
	<ul
		role="list"
		class="grid grid-cols-1 gap-x-6 gap-y-8 lg:grid-cols-3 xl:gap-x-8"
	>
		<li
			v-for="client in staff"
			:key="client.id"
			class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm bg-white dark:bg-gray-700"
		>
			<div
				class="flex items-center gap-x-4 border-b border-gray-900/5 bg-gray-50 dark:bg-gray-800 p-6"
			>
				<Avatar
					:image="client.person.image"
					:initials="client.person.initials"
				/>
				<div
					class="text-sm font-medium leading-6 text-gray-900 dark:text-gray-50"
				>
					{{ client.person.full_name }}
					<span class="block text-xs"
						>{{ client.start_date }} - {{ client.end_date ?? "to date" }}</span
					>
				</div>
				<Menu as="div" class="relative ml-auto">
					<MenuButton
						class="-m-2.5 block p-2.5 text-gray-400 hover:text-gray-500 dark:text-gray-200"
					>
						<span class="sr-only">Open options</span>
						<EllipsisHorizontalIcon class="h-5 w-5" aria-hidden="true" />
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
								<a
									href="#"
									:class="[
										active ? 'bg-gray-50 dark:bg-gray-800' : '',
										'block px-3 py-1 text-sm leading-6 text-gray-900 dark:text-gray-50',
									]"
									>View<span class="sr-only"
										>, {{ client.person.full_name }}</span
									></a
								>
							</MenuItem>
							<MenuItem v-slot="{ active }">
								<a
									href="#"
									:class="[
										active ? 'bg-gray-50 dark:bg-gray-800' : '',
										'block px-3 py-1 text-sm leading-6 text-gray-900 dark:text-gray-50',
									]"
									>Edit<span class="sr-only"
										>, {{ client.person.full_name }}</span
									></a
								>
							</MenuItem>
						</MenuItems>
					</transition>
				</Menu>
			</div>
			<dl class="-my-3 divide-y divide-gray-100 px-6 py-4 text-sm leading-6">
				<div class="flex justify-between gap-x-4 py-3">
					<dt class="text-gray-500 dark:text-gray-200">Current Rank</dt>
					<dd class="text-gray-700 dark:text-gray-300">
						<div class="font-medium text-gray-900 dark:text-gray-50">
							{{ client.current_rank }}
						</div>
						<time :datetime="client.current_rank_date">{{
							client.current_rank_date_display
						}}</time>
					</dd>
				</div>
				<div class="flex justify-between gap-x-4 py-3">
					<dt class="text-gray-500 dark:text-gray-200">Current Posting</dt>
					<dd class="flex items-start gap-x-2">
						<div class="font-medium text-gray-900 dark:text-gray-50">
							{{ client.current_unit }}
						</div>
						<time :datetime="client.current_unit_date">{{
							client.current_unit_date_display
						}}</time>
					</dd>
				</div>
			</dl>
		</li>
	</ul>
</template>

<script setup>
import Avatar from "@/Pages/Person/partials/Avatar.vue";
import { Menu, MenuButton, MenuItem, MenuItems } from "@headlessui/vue";
import { EllipsisHorizontalIcon } from "@heroicons/vue/20/solid";

const statuses = {
	Paid: "text-green-700 bg-green-50 ring-green-600/20",
	Withdraw: "text-gray-600 bg-gray-50 dark:bg-gray-800 ring-gray-500/10",
	Overdue: "text-red-700 bg-red-50 ring-red-600/10",
};
defineProps({
	staff: {
		type: Object,
		required: true,
	},
});
</script>
