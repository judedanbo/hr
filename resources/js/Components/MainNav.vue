<script setup>
import { Link } from '@inertiajs/inertia-vue3'
import { Cog6ToothIcon } from '@heroicons/vue/24/outline'
defineProps({
    navigation: Array,
    teams: Array,
});

</script>
<template>
    <nav class="flex flex-1 flex-co">
        <ul role="list" class="flex flex-1 flex-col gap-y-7">
            <li>
                <ul role="list" class="-mx-2 space-y-1">
                    <li v-for="item in navigation" :key="item.name">
                        <Link :href="item.href"
                            :class="[item.href == $page.props.ziggy.location ? 'bg-gray-100 text-green-600 font-bold tracking-wider dark:text-gray-800' : 'text-gray-800 dark:text-gray-50 hover:text-green-600 dark:hover:text-gray-600 hover:bg-gray-50', 'group flex gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold']">
                        <component :is="item.icon"
                            :class="[item.current ? 'text-green-600 dark:text-gray-800' : 'text-gray-800 dark:text-gray-50 group-hover:text-green-600 dark:group-hover:text-gray-600', 'h-6 w-6 shrink-0']"
                            aria-hidden="true" />
                        {{ item.name }}

                        </Link>
                    </li>
                </ul>
            </li>
            <!-- {{ $page.props.ziggy.location }} -->
            <!-- <li>
                <div class="text-xs font-semibold leading-6 text-gray-400">Your teams</div>
                <ul role="list" class="-mx-2 mt-2 space-y-1">
                    <li v-for="team in teams" :key="team.name">
                        <Link :href="team.href"
                            :class="[team.current ? 'bg-gray-50 text-green-600' : 'text-gray-800 dark:text-gray-50 hover:text-green-600 hover:bg-gray-50', 'group flex gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold']">
                            <span
                                :class="[team.current ? 'text-green-600 border-green-600' : 'text-gray-400 border-gray-200 group-hover:border-green-600 group-hover:text-green-600', 'flex h-6 w-6 shrink-0 items-center justify-center rounded-lg border text-[0.625rem] font-medium bg-white']">{{
                                    team.initial }}</span>
                            <span class="truncate">{{ team.name }}</span>
                        </Link>
                    </li>
                </ul>
            </li> -->
            <li class="mt-auto">
                <Link href="#"
                    class="group -mx-2 flex gap-x-3 rounded-md p-2 text-sm font-semibold leading-6 text-gray-800 dark:text-gray-50 hover:bg-gray-50 hover:text-green-600 dark:hover:text-gray-800">
                <Cog6ToothIcon
                    class="h-6 w-6 shrink-0 text-gray-400 group-hover:text-green-600 dark:group-hover:text-gray-700"
                    aria-hidden="true" />
                Settings
                </Link>
            </li>
        </ul>
    </nav>
</template>