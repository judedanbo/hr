<script setup>
import { Cog6ToothIcon } from "@heroicons/vue/24/outline";
defineProps({
	navigation: Array,
	teams: Array,
});
</script>
<template>
	<nav class="flex flex-1 flex-col">
		<ul role="list" class="flex flex-1 flex-col gap-y-7">
			<li>
				<ul role="list" class="-mx-2 space-y-1">
					<li v-for="item in navigation" :key="item.name">
						<a
							:href="item.href"
							:class="[
								item.current
									? 'bg-gray-50 text-indigo-600'
									: 'text-gray-700 hover:text-indigo-600 hover:bg-gray-50',
								'group flex gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold',
							]"
						>
							<component
								:is="item.icon"
								:class="[
									item.current
										? 'text-indigo-600'
										: 'text-gray-400 group-hover:text-indigo-600',
									'h-6 w-6 shrink-0',
								]"
								aria-hidden="true"
							/>
							{{ item.name }}
						</a>
					</li>
				</ul>
			</li>
			<li>
				<div class="text-xs font-semibold leading-6 text-gray-400">
					Your teams
				</div>
				<ul role="list" class="-mx-2 mt-2 space-y-1">
					<!-- <li v-for="team in teams" :key="team.name">
                        <a :href="team.href"
                            :class="[team.current ? 'bg-gray-50 text-indigo-600' : 'text-gray-700 hover:text-indigo-600 hover:bg-gray-50', 'group flex gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold']">
                            <span
                                :class="[team.current ? 'text-indigo-600 border-indigo-600' : 'text-gray-400 border-gray-200 group-hover:border-indigo-600 group-hover:text-indigo-600', 'flex h-6 w-6 shrink-0 items-center justify-center rounded-lg border text-[0.625rem] font-medium bg-white']">{{
                                    team.initial }}</span>
                            <span class="truncate">{{ team.name }}</span>
                        </a>
                    </li> -->
				</ul>
			</li>
			<li class="mt-auto">
				<a
					href="#"
					class="group -mx-2 flex gap-x-3 rounded-md p-2 text-sm font-semibold leading-6 text-gray-700 hover:bg-gray-50 hover:text-indigo-600"
				>
					<Cog6ToothIcon
						class="h-6 w-6 shrink-0 text-gray-400 group-hover:text-indigo-600"
						aria-hidden="true"
					/>
					Settings
				</a>
			</li>
		</ul>
	</nav>
</template>
