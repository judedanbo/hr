<script setup>
import { computed } from "vue";
import { Link } from "@inertiajs/vue3";

const props = defineProps({
	name: String,
	href: String,
	active: Boolean,
});
const classes = computed(() =>
	props.active
		? "flex justify-start items-center space-x-6 w-full focus:outline-none px-4 py-2 focus:font-semibold text-green-800 bg-green-50 font-semibold tracking-widest hover:bg-green-600"
		: "flex justify-start items-center space-x-6 w-full focus:outline-none pl-8 pr-4 py-2 focus:font-semibold text-white bg-green-800 hover:bg-green-600 text-sm",
);
</script>
<template>
	<Link :href="href" as="button" class="group" :class="classes">
		<slot name="icon" />
		<span class="hidden lg:flex">{{ name }}</span>
		<!-- <div
            class="inline-block absolute invisible group-hover:visible z-10 py-2 px-3 text-sm font-medium text-green-800 bg-green-50 rounded-lg shadow-sm opacity-0 group-hover:opacity-100 transition-opacity duration-300"
        >
            {{ name }}
        </div> -->
	</Link>
</template>
