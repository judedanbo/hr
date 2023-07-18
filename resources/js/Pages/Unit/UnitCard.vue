<script setup>
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue'
import {
    EllipsisHorizontalIcon,
} from '@heroicons/vue/20/solid'
import { Link } from '@inertiajs/inertia-vue3';

const emit = defineEmits(['editItem'])

const edit = (id) => {
    emit('editItem', id)
}
defineProps({
    unit: Object,
    statuses: Object,
})
</script>
<template>
    <li class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-500">
        <div class="flex items-center gap-x-4 border-b border-gray-900/5 bg-green-50 dark:bg-gray-500 p-6">

            <div class="text-sm font-medium leading-6 text-gray-900 dark:text-gray-50">{{ unit.name }}</div>
            <Menu as="div" class="relative ml-auto">
                <MenuButton class="-m-2.5 block p-2.5 text-gray-500 hover:text-gray-500">
                    <span class="sr-only">Open options</span>
                    <EllipsisHorizontalIcon class="h-5 w-5 dark:text-gray-50" aria-hidden="true" />
                </MenuButton>
                <transition enter-active-class="transition ease-out duration-100"
                    enter-from-class="transform opacity-0 scale-95" enter-to-class="transform opacity-100 scale-100"
                    leave-active-class="transition ease-in duration-75" leave-from-class="transform opacity-100 scale-100"
                    leave-to-class="transform opacity-0 scale-95">
                    <MenuItems
                        class="absolute right-0 z-10 mt-0.5 w-32 origin-top-right rounded-md bg-white dark:bg-gray-500 py-2 shadow-lg ring-1 ring-gray-900/5 focus:outline-none ">
                        <MenuItem>
                        <Link :href="route('unit.show', { unit: unit.id })"
                            class="block px-3 py-1 text-sm leading-6 text-gray-900 dark:text-gray-50 hover:bg-gray-800">View<span
                            class="sr-only">, {{
                                unit.name }}</span></Link>
                        </MenuItem>
                        <MenuItem>
                        <p @click.prevent="edit(unit.id)"
                            class="block px-3 py-1 text-sm leading-6 text-gray-900 dark:text-gray-50 cursor-pointer hover:bg-gray-800">
                            Edit<span class="sr-only">,
                                {{
                                    unit.name }}</span></p>
                        </MenuItem>
                    </MenuItems>
                </transition>
            </Menu>
        </div>
        <dl class="-my-3 divide-y divide-gray-100 dark:divide-gray-500 px-6 py-4 text-sm leading-6">
            <div class="flex justify-between gap-x-4 py-3">
                <dt class="text-gray-500 dark:text-gray-50">Divisions</dt>
                <dd class="flex items-start gap-x-2">
                    <div class="font-medium text-gray-900 dark:text-gray-50">{{ unit.divisions }}</div>
                </dd>
            </div>
            <div class="flex justify-between gap-x-4 py-3">
                <dt class="text-gray-500 dark:text-gray-50">Staff</dt>
                <dd class="text-gray-500">
                    <div class="font-medium text-gray-900 dark:text-gray-50">{{ unit.staff }}</div>
                </dd>
            </div>
            <div class="flex justify-between gap-x-4 py-3">
                <dt class="text-gray-500 dark:text-gray-50">Units</dt>
                <dd class="flex items-start gap-x-2">
                    <div class="font-medium text-gray-900 dark:text-gray-50">{{ unit.units }}</div>
                </dd>
            </div>
        </dl>
    </li>
</template>