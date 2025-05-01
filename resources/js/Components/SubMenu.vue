<script setup>
import { Menu, MenuButton, MenuItem, MenuItems } from "@headlessui/vue";
import {
	EllipsisVerticalIcon,
	PlusIcon,
	EllipsisHorizontalIcon,
} from "@heroicons/vue/20/solid";

defineProps({
	items: { type: Array, default: () => [] },
	canEdit: { type: Boolean, default: false },
	canDelete: { type: Boolean, default: false },
});
const emit = defineEmits(["itemClicked"]);
</script>
<template>
	<Menu as="div" class="relative">
		<MenuButton
			class="ml-3 block py-3 rounded-lg hover:bg-green-50 dark:hover:bg-gray-50 group"
		>
			<span class="sr-only">More</span>
			<EllipsisVerticalIcon
				class="h-5 w-5 text-gray-500 dark:text-gray-300 group-hover:text-green-500 dark:group-hover:text-gray-700"
				aria-hidden="true"
			/>
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
				class="absolute right-5 -top-3 z-50 mt-0.5 w-32 origin-top-right rounded-md bg-white dark:bg-gray-500 py-2 shadow-lg ring-1 ring-gray-900/5 focus:outline-none"
			>
				<template v-for="item in items" :key="item">
					<MenuItem
						v-if="
							(item === 'Edit' && canEdit) || (item === 'Delete' && canDelete)
						"
						v-slot="{ active }"
						@click="emit('itemClicked', item)"
					>
						<button
							type="button"
							:class="[
								active ? 'bg-gray-50' : '',
								'block w-full py-1 px-4 text-left text-sm leading-6 text-gray-900 dark:text-white dark:hover:text-gray-900',
							]"
						>
							{{ item }}
						</button>
					</MenuItem>
				</template>
			</MenuItems>
		</transition>
	</Menu>
</template>
