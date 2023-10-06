<script setup>
import { Menu, MenuButton, MenuItem, MenuItems } from "@headlessui/vue";
import {
    EllipsisVerticalIcon,
    PlusIcon,
    EllipsisHorizontalIcon,
} from "@heroicons/vue/20/solid";

defineProps({
    items: Array,
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
                class="absolute right-0 z-10 mt-0.5 w-32 origin-top-right rounded-md bg-white dark:bg-gray-500 py-2 shadow-lg ring-1 ring-gray-900/5 focus:outline-none"
            >
                <MenuItem
                    @click="emit('itemClicked', item)"
                    v-for="item in items"
                    :key="item"
                    v-slot="{ active }"
                >
                    <button
                        type="button"
                        :class="[
                            active ? 'bg-gray-50' : '',
                            'block w-full py-1 text-right text-sm leading-6 text-gray-900 dark:text-white dark:hover:text-gray-900',
                        ]"
                    >
                        {{ item }}
                    </button>
                </MenuItem>
            </MenuItems>
        </transition>
    </Menu>
</template>
