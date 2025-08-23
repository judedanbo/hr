<script setup>
import { Disclosure, DisclosureButton, DisclosurePanel } from "@headlessui/vue";
import { Bars3Icon, BellIcon, XMarkIcon } from "@heroicons/vue/24/outline";
import PageMenuItem from "@/Components/PageMenuItem.vue";
import PgMenuItem from "@/Components/PgMenuItem.vue";
defineProps({
	pageMenu: {
		type: Array,
		required: true,
	},
});
</script>

<template>
	<Disclosure
		v-slot="{ open }"
		as="nav"
		class="bg-gray-100 dark:bg-gray-600 shadow shadow-gray-50 dark:shadow-gray-700"
	>
		<div class="mx-auto max-w-7xl px-2 sm:px-4 lg:px-8">
			<div class="flex h-16 justify-between">
				<div class="flex px-2 lg:px-0">
					<div class="hidden lg:ml-6 lg:flex lg:space-x-8">
						<PageMenuItem
							v-for="(page, index) in pageMenu"
							:key="index"
							:page="page"
						/>
					</div>
				</div>

				<div class="flex items-center lg:hidden">
					<!-- Mobile menu button -->
					<DisclosureButton
						class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500"
					>
						<span class="sr-only">Open main menu</span>
						<Bars3Icon v-if="!open" class="block h-6 w-6" aria-hidden="true" />
						<XMarkIcon v-else class="block h-6 w-6" aria-hidden="true" />
					</DisclosureButton>
				</div>
			</div>
		</div>

		<DisclosurePanel class="lg:hidden">
			<div class="space-y-1 pb-3 pt-2">
				<PgMenuItem
					v-for="(page, index) in pageMenu"
					:key="index"
					:page="page"
				/>
			</div>
		</DisclosurePanel>
	</Disclosure>
</template>
