<script setup>
import { usePage } from "@inertiajs/vue3";
import { computed } from "vue";
import { XMarkIcon } from "@heroicons/vue/16/solid";

defineProps({
	roles: { type: Array, default: () => [] },
});

const emit = defineEmits(["deleteRole"]);

const page = usePage();
const permissions = computed(() => page.props?.auth.permissions);
const canRevoke = computed(() =>
	permissions.value?.includes("assign roles to user"),
);
</script>

<template>
	<div class="px-5 pb-5">
		<ul v-if="roles?.length > 0" class="flex flex-wrap gap-2">
			<li
				v-for="role in roles"
				:key="role.id"
				class="inline-flex items-center gap-1.5 rounded-full bg-green-50 dark:bg-gray-700 pl-3 pr-1.5 py-1 text-sm font-medium text-green-800 dark:text-green-200 ring-1 ring-inset ring-green-600/20"
			>
				<span class="h-1.5 w-1.5 rounded-full bg-green-500" />
				{{ role.name }}
				<button
					v-if="canRevoke"
					type="button"
					class="rounded-full p-0.5 text-green-500 hover:bg-green-100 hover:text-green-700 dark:hover:bg-gray-600"
					:aria-label="`Revoke ${role.name}`"
					@click="emit('deleteRole', role)"
				>
					<XMarkIcon class="h-4 w-4" />
				</button>
			</li>
		</ul>
		<p
			v-else
			class="py-6 text-center text-sm font-medium text-gray-400 dark:text-gray-300"
		>
			No roles assigned to this user.
		</p>
	</div>
</template>
