<script setup>
import { Head, Link } from "@inertiajs/inertia-vue3";
import { useDark, useToggle } from "@vueuse/core";
import { SunIcon, MoonIcon } from "@heroicons/vue/24/outline";

defineProps({
	canLogin: Boolean,
	canRegister: Boolean,
	year: String,
	logo: String,
});

const dark = useDark();
const toggle = useToggle(dark);
</script>

<template>
	<Head title="Welcome" />

	<div
		class="relative flex items-top justify-center min-h-screen bg-gray-100 dark:bg-gray-900 items-center sm:pt-0"
	>
		<div class="lg:flex flex-wrap w-full md:max-w- sm:px-6 lg:px-0 max-w-6xl">
			<img
				class="mx-auto lg:w-1/3"
				:src="logo"
				alt="Audit Service"
				width="150"
			/>
			<div
				class="flex items-center justify-center flex-col mt-8 p-12 text-center w-full lg:w-1/2 sm:rounded-xl bg-green-700 lg:bg-transparent"
			>
				<h1
					class="text-white lg:text-green-900 dark:text-gray-50 text-2xl mb-6"
				>
					Audit Service, Ghana
				</h1>
				<h1
					class="text-white lg:text-green-900 dark:text-gray-50 text-4xl hidden md:block"
				>
					Human Resource Management Information System
				</h1>
				<h1 class="text-white text-4xl md:hidden">HRMIS</h1>
				<Link
					v-if="$page.props.auth.user"
					:href="route('dashboard')"
					as="button"
					class="mt-12 text-gray-700 lg:text-white text-2xl lg:text-3xl bg-white lg:bg-green-800 hover:bg-green-900 hover:text-white focus:outline-none hover:ring-green-600 rounded-lg px-8 py-3.5 text-center tracking-widest"
				>
					Home
				</Link>
				<div v-else class="space-x-4">
					<Link
						v-if="canLogin"
						:href="route('login')"
						as="button"
						class="mt-12 text-gray-700 lg:text-white text-2xl lg:text-3xl bg-white lg:bg-green-800 hover:bg-green-900 hover:text-white focus:outline-none focus:ring-1 focus:ring-green-300 hover:ring-green-600 rounded-lg px-8 py-3.5 text-center tracking-widest"
					>
						Login
					</Link>
					<Link
						v-if="canRegister"
						:href="route('register')"
						as="button"
						class="mt-12 text-gray-700 lg:text-green-800 text-2xl lg:text-3xl bg-white lg:bg-transparent hover:bg-green-600 hover:text-white focus:outline-none focus:ring-1 focus:ring-green-700 hover:underline hover:ring-green-600 rounded-lg px-8 py-3.5 text-center tracking-widest"
					>
						Register
					</Link>
				</div>
				<div class="mt-2 cursor-pointer">
					<SunIcon
						@click="toggle()"
						v-if="dark"
						class="w-5 h-5 rounded-full bg-white"
					/>
					<MoonIcon @click="toggle()" v-else class="w-5 h-5 rounded-full" />
				</div>
			</div>
			<div class="text-right pr-8 mt-3 lg:w-full dark:text-gray-100">
				&copy; {{ year }} Audit Service, Ghana
			</div>
		</div>
	</div>
</template>
