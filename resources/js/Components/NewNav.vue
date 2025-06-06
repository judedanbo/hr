<template>
	<nav class="flex flex-1 flex-col">
		<ul role="list" class="flex flex-1 flex-col gap-y-7">
			<li>
				<ul role="list" class="-mx-2 space-y-1">
					<li v-for="item in navigation" :key="item.name">
						<template v-if="item.visible !== false">
							<Link
								v-if="!item.children"
								:href="item.href"
								:class="[
									item.current
										? 'bg-green-50 text-green-800 font-bold tracking-wider dark:text-gray-800 border-l-4 border-solid border-green-800'
										: 'text-gray-800 dark:text-gray-50 dark:hover:text-gray-600',
									'group flex gap-x-3 py-2 px-8  text-sm leading-6 font-semibold tracking-wider hover:bg-green-100 hover:text-green-800',
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
							<Disclosure v-else v-slot="{ open }" as="div">
								<DisclosureButton
									:class="[
										item.current
											? 'bg-green-50 text-green-800 font-bold tracking-wider dark:text-gray-800 border-l-4 border-solid border-green-800'
											: 'text-gray-800 dark:text-gray-50 dark:hover:text-gray-600',
										'flex w-full gap-x-3 py-2 px-8  text-sm leading-6 font-semibold tracking-wider hover:bg-green-100 hover:text-green-800',
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
									<ChevronRightIcon
										:class="[
											open ? 'rotate-90 text-gray-800' : 'text-gray-400',
											'ml-auto h-5 w-5 shrink-0',
										]"
										aria-hidden="true"
									/>
								</DisclosureButton>
								<DisclosurePanel as="ul" class="mt-1 px-2">
									<li v-for="subItem in item.children" :key="subItem.name">
										<!-- 44px -->
										<DisclosureButton
											as="a"
											:href="subItem.href"
											:class="[
												subItem.current
													? 'bg-green-50 text-green-800 font-bold tracking-wider dark:text-gray-800 border-l-4 border-solid border-green-800'
													: 'text-gray-800 dark:text-gray-50 dark:hover:text-gray-600',
												'block rounded-md py-2 pl-9 px-8 text-xs leading-6 font-semibold tracking-wider hover:bg-green-100 hover:text-green-800',
											]"
											>{{ subItem.name }}</DisclosureButton
										>
									</li>
								</DisclosurePanel>
							</Disclosure>
						</template>
					</li>
				</ul>
			</li>
			<li v-if="permissions.includes('view admin settings')" class="mt-auto">
				<Link
					:href="route('settings.index')"
					class="group flex gap-x-3 rounded-md py-2 px-6 text-sm font-semibold leading-6 text-gray-800 dark:text-gray-50 hover:bg-green-50 hover:text-green-600 dark:hover:text-gray-800"
				>
					<Cog6ToothIcon
						class="h-6 w-6 shrink-0 text-gray-400 dark:text-green-50 group-hover:text-green-600 dark:group-hover:text-gray-700"
						aria-hidden="true"
					/>
					Settings
				</Link>
			</li>
		</ul>
	</nav>
</template>

<script setup>
import { Link, usePage } from "@inertiajs/inertia-vue3";
import { Disclosure, DisclosureButton, DisclosurePanel } from "@headlessui/vue";
import { ChevronRightIcon } from "@heroicons/vue/20/solid";
import { computed } from "vue";
import { Cog6ToothIcon } from "@heroicons/vue/24/outline";

defineProps({
	navigation: {
		type: Array,
		required: true,
	},
});
const page = usePage();
const permissions = computed(() => page.props.value.auth.permissions);
</script>
