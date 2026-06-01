<script setup>
const props = defineProps({
	user: { type: Object, required: true },
	canManage: { type: Boolean, default: false },
});

const emit = defineEmits(["associate", "unlink"]);
</script>

<template>
	<div
		class="rounded-2xl border border-green-200/60 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm p-5"
	>
		<h2 class="text-sm font-semibold text-green-900 dark:text-gray-100">
			Staff record
		</h2>
		<p class="mt-2 text-sm text-gray-700 dark:text-gray-200">
			{{
				user.staff
					? `${user.staff.name} — ${user.staff.staff_number ?? "—"}`
					: "Not linked"
			}}
		</p>
		<div v-if="canManage" class="mt-4 flex gap-3">
			<button
				type="button"
				class="rounded-md bg-green-600 px-2.5 py-1 text-xs font-medium text-white hover:bg-green-500 dark:bg-gray-600 dark:hover:bg-gray-500"
				@click="emit('associate')"
			>
				{{ user.person_id ? "Change" : "Associate" }}
			</button>
			<button
				v-if="user.person_id"
				type="button"
				class="rounded-md px-2.5 py-1 text-xs font-medium text-red-600 ring-1 ring-inset ring-red-600/20 dark:text-red-400 dark:ring-red-400/30 hover:bg-red-50 dark:hover:bg-gray-700"
				@click="emit('unlink')"
			>
				Unlink
			</button>
		</div>
	</div>
</template>
