<script setup>
import { Link } from "@inertiajs/inertia-vue3";
import { Cog6ToothIcon } from "@heroicons/vue/24/outline";
defineProps({
	navigation: { type: Array, required: true },
});
</script>
<template>
	<nav class="flex flex-1 flex-col">
		<ul role="list" class="flex flex-1 flex-col gap-y-7">
			<li>
				<ul role="list" class="space-y-1">
					<li v-for="item in navigation" :key="item.name">
						<Link
							v-if="item.visible !== false"
							:href="item.href"
							:class="[
								item.current
									? 'bg-green-50 text-green-800 font-bold tracking-wider dark:text-gray-800 border-l-4 border-solid border-green-800 '
									: 'text-gray-800 dark:text-gray-50 dark:hover:text-gray-600 ',
								'group flex gap-x-3 py-2 px-4  text-sm leading-6 font-semibold tracking-wider hover:bg-green-100 hover:text-green-800 ',
							]"
						>
							<component
								:is="item.icon"
								:class="[
									item.current
										? 'text-green-800 dark:text-gray-800'
										: 'text-gray-800 dark:text-gray-50 group-hover:text-green-600 dark:group-hover:text-gray-600',
									'h-6 w-6 shrink-0',
								]"
								aria-hidden="true"
							/>
							{{ item.name }}
						</Link>
					</li>
				</ul>
			</li>
			<li class="mt-auto">
				<Link
					:href="route('user.index')"
					class="group flex gap-x-3 rounded-md py-2 text-sm font-semibold leading-6 text-gray-800 dark:text-gray-50 hover:bg-green-50 hover:text-green-600 dark:hover:text-gray-800"
				>
					<Cog6ToothIcon
						class="h-6 w-6 shrink-0 text-gray-400 group-hover:text-green-600 dark:group-hover:text-gray-700"
						aria-hidden="true"
					/>
					Settings
				</Link>
			</li>
		</ul>
	</nav>
</template>
