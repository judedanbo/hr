<script setup>
import { Popover, PopoverButton, PopoverPanel } from "@headlessui/vue";
import { ChevronDownIcon } from "@heroicons/vue/20/solid";
import { Link } from "@inertiajs/vue3";

const emit = defineEmits(["editItem", "deleteItem"]);

let props = defineProps({
	name: String,
	path: String,
	route_id: Number,
});
</script>

<template>
	<Popover class="relative">
		<PopoverButton
			class="inline-flex items-center gap-x-1 text-sm font-semibold leading-6 text-gray-900"
		>
			<span class="text-green-600">{{ name }}</span>
			<ChevronDownIcon class="h-5 w-5 text-green-600" aria-hidden="true" />
		</PopoverButton>
		<transition
			enter-active-class="transition ease-out duration-200"
			enter-from-class="opacity-0 translate-y-1"
			enter-to-class="opacity-100 translate-y-0"
			leave-active-class="transition ease-in duration-150"
			leave-from-class="opacity-100 translate-y-0"
			leave-to-class="opacity-0 translate-y-1"
		>
			<PopoverPanel
				class="absolute z-10 mt-5 flex w-screen max-w-min -translate-x-1/2 px-4"
			>
				<div
					class="w-40 shrink bg-white text-sm font-semibold leading-6 text-gray-900 shadow-lg ring-1 ring-gray-900/5"
				>
					<Link
						:href="route('institution.show', { institution: route_id })"
						class="block p-2 hover:text-green-600 text-left px-4 hover:bg-gray-300"
						>View
					</Link>
					<p
						class="block p-2 hover:text-green-600 text-left px-4 hover:bg-gray-300 cursor-pointer"
						@click="emit('editItem', $event, route_id)"
					>
						Edit
					</p>
					<p
						class="block p-2 hover:text-green-600 text-left px-4 hover:bg-gray-300 cursor-pointer"
						@click="emit('deleteItem', $event, route_id)"
					>
						Delete
					</p>
				</div>
			</PopoverPanel>
		</transition>
	</Popover>
</template>
