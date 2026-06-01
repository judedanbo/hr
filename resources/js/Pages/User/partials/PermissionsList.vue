<script setup>
import { usePage } from "@inertiajs/vue3";
import { computed } from "vue";
import { XMarkIcon } from "@heroicons/vue/16/solid";

defineProps({
	permissions: { type: Array, default: () => [] },
});

const emit = defineEmits(["deletePermission"]);

const page = usePage();
const authPermissions = computed(() => page.props?.auth.permissions);
const canRevoke = computed(() =>
	authPermissions.value?.includes("assign permissions to user"),
);
</script>

<template>
	<ul v-if="permissions?.length > 0" class="flex flex-wrap gap-2">
		<li
			v-for="permission in permissions"
			:key="permission.id"
			class="inline-flex items-center gap-1.5 rounded-full bg-green-50 dark:bg-gray-700 pl-3 pr-1.5 py-1 text-sm font-medium text-green-800 dark:text-green-200 ring-1 ring-inset ring-green-600/20"
		>
			{{ permission.name }}
			<button
				v-if="canRevoke"
				type="button"
				class="rounded-full p-0.5 text-green-500 hover:bg-green-100 hover:text-green-700 dark:hover:bg-gray-600"
				:aria-label="`Revoke ${permission.name}`"
				@click="emit('deletePermission', permission)"
			>
				<XMarkIcon class="h-4 w-4" />
			</button>
		</li>
	</ul>
	<p
		v-else
		class="py-4 text-sm font-medium text-gray-400 dark:text-gray-300"
	>
		No direct permissions.
	</p>
</template>
