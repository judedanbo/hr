<script setup>
import { useDark, useToggle } from "@vueuse/core";
import { Link } from "@inertiajs/inertia-vue3";

import { Menu, MenuButton, MenuItem, MenuItems } from "@headlessui/vue";
import { BellIcon, MoonIcon, SunIcon } from "@heroicons/vue/24/outline";
import { ChevronDownIcon, MagnifyingGlassIcon } from "@heroicons/vue/20/solid";

const isDark = useDark();
const toggleDark = useToggle(isDark);

defineProps({
	userNavigation: Array,
});
</script>
<template>
	<div
		class="flex flex-1 gap-x-4 self-stretch lg:gap-x-6 dark:bg-gray-800 justify-end"
	>
		<div class="flex items-center gap-x-4 lg:gap-x-6">
			<button
				type="button"
				class="-m-2.5 p-2.5 text-gray-700 hover:text-gray-500 dark:text-gray-50 dark:hover:text-gray-200"
			>
				<span class="sr-only">Toggle Dark /Light mode</span>
				<SunIcon
					@click="toggleDark()"
					v-if="isDark"
					class="h-6 w-6"
					aria-hidden="true"
				/>
				<MoonIcon
					@click="toggleDark()"
					v-else
					class="h-6 w-6"
					aria-hidden="true"
				/>
			</button>

			<div
				class="hidden lg:block lg:h-6 lg:w-px lg:bg-gray-200"
				aria-hidden="true"
			/>

			<!-- Separator -->
			<button
				type="button"
				class="-m-2.5 p-2.5 text-gray-400 hover:text-gray-500"
			>
				<span class="sr-only">View notifications</span>
				<BellIcon
					class="h-6 w-6 text-gray-700 dark:text-gray-50"
					aria-hidden="true"
				/>
			</button>

			<!-- Separator -->
			<div
				class="hidden lg:block lg:h-6 lg:w-px lg:bg-gray-200"
				aria-hidden="true"
			/>

			<!-- Profile dropdown -->
			<Menu as="div" class="relative">
				<MenuButton class="-m-1.5 flex items-center p-1.5">
					<span class="sr-only">Open user menu</span>
					<img
						class="h-8 w-8 rounded-full bg-gray-50"
						src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80"
						alt=""
					/>
					<span class="hidden lg:flex lg:items-center">
						<span
							class="ml-4 text-sm font-semibold leading-6 text-gray-900 dark:text-gray-50"
							aria-hidden="true"
							>{{ $page.props.auth.user.name }}</span
						>
						<ChevronDownIcon
							class="ml-2 h-5 w-5 text-gray-400"
							aria-hidden="true"
						/>
					</span>
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
						class="absolute right-0 z-10 mt-2.5 w-32 origin-top-right rounded-md bg-white dark:bg-gray-700 py-2 shadow-lg ring-1 ring-gray-900/5 focus:outline-none"
					>
						<MenuItem
							v-for="item in userNavigation"
							:key="item.name"
							v-slot="{ active }"
						>
							<Link
								:href="item.href"
								method="post"
								as="button"
								:class="[
									active ? 'bg-gray-50' : '',
									'block px-3 py-1 text-sm leading-6 text-gray-900 dark:text-gray-50 dark:hover:text-gray-800',
								]"
								class="w-full text-left"
							>
								{{ item.name }}</Link
							>
						</MenuItem>
					</MenuItems>
				</transition>
			</Menu>
		</div>
	</div>
</template>
