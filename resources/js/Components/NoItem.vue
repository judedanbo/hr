<script setup>
import { InboxIcon } from "@heroicons/vue/24/outline";

defineProps({
	name: { type: String, default: "items" },
});
</script>
<template>
	<div
		class="flex flex-col items-center justify-center gap-2 px-6 py-12 text-center"
	>
		<InboxIcon
			class="h-8 w-8 text-gray-300 dark:text-gray-600"
			aria-hidden="true"
		/>
		<p class="text-sm font-medium text-gray-900 dark:text-gray-100">
			No {{ name }} found
		</p>
		<p class="text-xs text-gray-500 dark:text-gray-400">
			There is nothing to show here yet.
		</p>
		<slot name="action" />
	</div>
</template>
