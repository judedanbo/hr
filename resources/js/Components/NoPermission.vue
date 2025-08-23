<script setup lang="ts">
import { NoSymbolIcon } from "@heroicons/vue/20/solid";
import { router } from "@inertiajs/vue3";
import { Link } from "@inertiajs/vue3";
</script>

<template>
	<div class="flex flex-col items-center justify-center h-screen">
		<NoSymbolIcon class="h-12 w-12 text-gray-400 dark:text-gray-200" />
		<h1 class="mt-4 text-lg font-semibold text-gray-900 dark:text-gray-50">
			You do not have permission to view this page
		</h1>
		<p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
			Please contact your administrator if you believe this is an error.
		</p>
		<p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
			<Link
				href="#"
				class="text-green-600 hover:text-green-500 dark:text-green-500"
				@click.prevent="router.reload()"
			>
				Reload
			</Link>
		</p>
	</div>
</template>
