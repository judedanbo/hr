<script setup>
defineProps({
	user: { type: Object, required: true },
});
</script>

<template>
	<div
		class="rounded-2xl border border-green-200/60 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm p-5"
	>
		<h2 class="text-sm font-semibold text-green-900 dark:text-gray-100">
			Account
		</h2>
		<dl class="mt-3 space-y-3 text-sm">
			<div class="flex justify-between gap-3">
				<dt class="text-gray-500 dark:text-gray-400">Email</dt>
				<dd class="text-gray-900 dark:text-gray-100 truncate">
					{{ user.email }}
				</dd>
			</div>
			<div class="flex justify-between gap-3">
				<dt class="text-gray-500 dark:text-gray-400">Verified</dt>
				<dd class="text-gray-900 dark:text-gray-100">{{ user.verified }}</dd>
			</div>
			<div class="flex justify-between gap-3">
				<dt class="text-gray-500 dark:text-gray-400">User ID</dt>
				<dd class="text-gray-900 dark:text-gray-100">#{{ user.id }}</dd>
			</div>
		</dl>
	</div>
</template>
