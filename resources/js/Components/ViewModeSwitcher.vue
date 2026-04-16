<script setup>
import { computed } from "vue";
import { usePage, router } from "@inertiajs/vue3";
import { Menu, MenuButton, MenuItems, MenuItem } from "@headlessui/vue";
import { ChevronDownIcon } from "@heroicons/vue/20/solid";

const page = usePage();

const isMultiRoleStaff = computed(
    () => page.props.auth.isMultiRoleStaff,
);
const viewMode = computed(() => page.props.auth.viewMode);
const viewModeLabel = computed(() => page.props.auth.viewModeLabel);

const buttonLabel = computed(() => {
    if (!viewMode.value) return "Choose view";
    if (viewMode.value === "staff") return "Viewing as: Staff";
    return `Viewing as: ${viewModeLabel.value}`;
});

const oppositeMode = computed(() =>
    viewMode.value === "staff" ? "other" : "staff",
);

const oppositeLabel = computed(() => {
    if (!viewMode.value) return "Go to chooser";
    if (oppositeMode.value === "staff") return "Switch to Staff view";
    return `Switch to ${viewModeLabel.value} view`;
});

function handleClick() {
    if (!viewMode.value) {
        router.visit(route("dashboard.choose-mode"));
        return;
    }
    router.post(route("dashboard.switch-mode"), {
        mode: oppositeMode.value,
    });
}
</script>

<template>
    <Menu v-if="isMultiRoleStaff" as="div" class="relative">
        <MenuButton
            class="flex items-center gap-1 rounded-md px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-50 dark:hover:bg-gray-700"
        >
            {{ buttonLabel }}
            <ChevronDownIcon
                class="h-4 w-4 text-gray-400"
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
                class="absolute right-0 z-10 mt-2 origin-top-right rounded-md bg-white dark:bg-gray-700 py-1 shadow-lg ring-1 ring-gray-900/5 focus:outline-none"
            >
                <MenuItem v-slot="{ active }">
                    <button
                        type="button"
                        :class="[
                            active ? 'bg-gray-50 dark:bg-gray-600' : '',
                            'block w-full px-3 py-1.5 text-left text-sm text-gray-900 dark:text-gray-50',
                        ]"
                        @click="handleClick"
                    >
                        {{ oppositeLabel }}
                    </button>
                </MenuItem>
            </MenuItems>
        </transition>
    </Menu>
</template>
