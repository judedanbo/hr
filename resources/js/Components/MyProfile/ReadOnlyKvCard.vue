<script setup>
defineProps({
	title: { type: String, required: true },
	rows: {
		type: Array,
		required: true,
	},
	lockLabel: { type: String, default: "" },
	footer: { type: String, default: "" },
});
</script>

<template>
	<section
		class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-sm"
	>
		<header class="flex justify-between items-center mb-3">
			<h3 class="text-sm font-bold text-gray-900 dark:text-gray-50">
				{{ title }}
			</h3>
			<span
				v-if="lockLabel"
				class="text-[11px] text-gray-400 dark:text-gray-500"
				>{{ lockLabel }}</span
			>
		</header>

		<dl class="divide-y divide-gray-100 dark:divide-gray-700 text-sm">
			<div
				v-for="row in rows"
				:key="row.key"
				class="flex justify-between py-1.5"
			>
				<dt class="text-gray-500 dark:text-gray-400">{{ row.key }}</dt>
				<dd
					class="font-medium text-gray-900 dark:text-gray-100 truncate max-w-[60%] text-right"
				>
					{{ row.value }}
				</dd>
			</div>
		</dl>

		<p
			v-if="footer"
			class="mt-3 text-[11px] italic text-gray-500 dark:text-gray-400"
		>
			{{ footer }}
		</p>
	</section>
</template>
