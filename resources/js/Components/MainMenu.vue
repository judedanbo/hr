<script setup>
import { ref, computed, useSlots } from "vue";
import { Link } from "@inertiajs/inertia-vue3";
import { ChevronUpIcon, BellIcon } from "@heroicons/vue/24/outline";
const hasSlots = useSlots();

const props = defineProps({
	name: String,
	href: {
		type: String,
		default: "",
	},
	active: Boolean,
});
const isOpen = ref(false);

const classes = computed(() =>
	props.active
		? "focus:outline-none focus:font-bold text-white flex justify-between items-center w-full py-2 group-hover:bg-green-600 bg-green-50 text-green-800 font-bold tracking-widest group-hover:text-green-800 pl-2 lg:pl-4 pr-2"
		: "focus:outline-none focus:font-bold text-white flex justify-between items-center w-full py-2 group-hover:bg-green-600 group-hover:text-green-800 pl-2 lg:pl-4 pr-2",
);
</script>

<template>
	<div
		:class="{ 'h-10': isOpen }"
		class="flex flex-col justify-start items-center w-full group overflow-hidden"
	>
		<Link v-if="href != ''" :class="classes" :href="href">
			<span class="flex items-center gap-4 group-hover:text-white">
				<slot name="icon" />
				<p
					class="leading-4 tracking-wider group-hover:text-white hidden lg:flex"
				>
					{{ name }}
				</p>
			</span>
			<ChevronUpIcon
				v-if="hasSlots.default"
				@click="isOpen = !isOpen"
				:class="isOpen === true ? 'rotate-90' : 'rotate-180'"
				class="w-6 p-1 text-white group-hover:bg-green-50 rounded-full group-hover:text-green-800 transition-all"
			/>
			<!-- <div
                class="inline-block absolute invisible group-hover:visible z-10 py-2 px-3 text-sm font-medium text-green-800 bg-green-50 rounded-lg shadow-sm opacity-0 group-hover:opacity-100 transition-opacity duration-300"
            >
                {{ name }} -->
			<!-- </div> -->
		</Link>
		<div v-else :class="classes">
			<span class="flex items-center gap-4 group-hover:text-white">
				<slot name="icon" />
				<p
					class="leading-4 tracking-wider group-hover:text-white hidden lg:flex"
				>
					{{ name }}
				</p>
			</span>
			<ChevronUpIcon
				v-if="hasSlots.default"
				@click="isOpen = !isOpen"
				:class="isOpen === true ? 'rotate-90' : 'rotate-180'"
				class="w-6 p-1 text-white group-hover:bg-green-50 rounded-full group-hover:text-green-800 transition-all"
			/>
			<!-- <div
                class="inline-block absolute invisible group-hover:visible z-10 py-2 px-3 text-sm font-medium text-green-800 bg-green-50 rounded-lg shadow-sm opacity-0 group-hover:opacity-100 transition-opacity duration-300"
            >
                {{ name }}
            </div> -->
		</div>
		<slot />
	</div>
</template>
