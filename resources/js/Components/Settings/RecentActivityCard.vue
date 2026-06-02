<script setup>
defineProps({
	activities: { type: Array, default: () => [] },
});
</script>

<template>
	<div
		class="rounded-2xl border border-green-200/60 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm p-5"
	>
		<h2 class="text-sm font-semibold text-green-900 dark:text-gray-100">
			Recent admin activity
		</h2>
		<ul
			v-if="activities.length > 0"
			class="mt-3 divide-y divide-gray-100 dark:divide-gray-700"
		>
			<li
				v-for="activity in activities"
				:key="activity.id"
				class="flex items-center justify-between gap-3 py-2 text-sm"
			>
				<span class="min-w-0 truncate text-gray-700 dark:text-gray-200">
					<span class="font-medium text-gray-900 dark:text-gray-100">{{
						activity.causer_name
					}}</span>
					{{ activity.description }}
				</span>
				<span class="flex-shrink-0 text-xs text-gray-400 dark:text-gray-400">
					{{ activity.created_at }}
				</span>
			</li>
		</ul>
		<p v-else class="mt-3 text-sm text-gray-400 dark:text-gray-300">
			No recent activity.
		</p>
	</div>
</template>
