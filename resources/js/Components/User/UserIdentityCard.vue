<script setup>
import { computed } from "vue";
import { CheckBadgeIcon } from "@heroicons/vue/20/solid";

const props = defineProps({
	user: { type: Object, required: true },
});

const initials = computed(() =>
	(props.user.name ?? "")
		.split(" ")
		.filter(Boolean)
		.map((part) => part[0])
		.slice(0, 2)
		.join("")
		.toUpperCase(),
);

const primaryRole = computed(() => props.user.roles?.[0]?.name ?? null);
const isVerified = computed(() => props.user.verified === "Yes");
</script>

<template>
	<div
		class="rounded-2xl border border-green-200/60 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm p-5 sm:p-6 flex flex-col sm:flex-row items-center sm:items-stretch gap-4 sm:gap-5"
	>
		<div class="flex-shrink-0">
			<div
				class="w-[72px] h-[72px] rounded-full bg-gradient-to-br from-green-500 to-green-700 dark:from-gray-500 dark:to-gray-700 flex items-center justify-center text-white font-bold text-2xl"
			>
				{{ initials }}
			</div>
		</div>
		<div class="flex-1 min-w-0 text-center sm:text-left">
			<h1
				class="text-xl sm:text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-50"
			>
				{{ user.name }}
			</h1>
			<p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
				<span v-if="primaryRole">{{ primaryRole }}</span>
				<span v-if="primaryRole && user.staff?.staff_number"> · </span>
				<span v-if="user.staff?.staff_number"
					>Staff #{{ user.staff.staff_number }}</span
				>
			</p>
		</div>
		<div
			class="flex flex-col items-center sm:items-end justify-center gap-1 text-sm"
		>
			<span class="text-gray-600 dark:text-gray-300">{{ user.email }}</span>
			<span
				v-if="isVerified"
				class="inline-flex items-center gap-1 rounded-full bg-green-50 dark:bg-gray-700 px-2 py-0.5 text-xs font-medium text-green-700 dark:text-green-300 ring-1 ring-inset ring-green-600/20"
			>
				<CheckBadgeIcon class="h-4 w-4" /> Verified
			</span>
			<span
				v-else
				class="inline-flex items-center rounded-full bg-yellow-50 dark:bg-gray-700 px-2 py-0.5 text-xs font-medium text-yellow-700 dark:text-yellow-300 ring-1 ring-inset ring-yellow-600/20"
			>
				Unverified
			</span>
		</div>
	</div>
</template>
