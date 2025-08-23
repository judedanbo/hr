<script setup>
import { router } from "@inertiajs/vue3";
import { usePage } from "@inertiajs/vue3";
import { onMounted, ref, computed } from "vue";
import { CheckIcon } from "@heroicons/vue/24/solid";
const emit = defineEmits(["formSubmitted"]);

const props = defineProps({
	role: { type: Number, required: true },
	// rolePermissions: {
	// 	type: Array,
	// 	default: () => [],
	// },
});

const List = ref([]);
const rolePermissions = ref([]);

onMounted(async () => {
	const rolePermissionsResponse = await axios.get(
		route("role.permissions", { role: props.role }),
	);
	rolePermissions.value = rolePermissionsResponse.data.map((permission) => {
		return permission;
	});
	const response = await axios.get(route("permission.list"));
	List.value = response.data.map((permission) => {
		return {
			value: permission.label,
			label: permission.label,
			displayName: permission.display_name,
		};
	});
});

const submitHandler = (data, node) => {
	router.post(route("role.add.permissions", { role: data.role }), data, {
		preserveScroll: true,
		onSuccess: () => {
			node.reset();
			emit("formSubmitted");
		},
		onError: (errors) => {
			node.setErrors([""], errors);
		},
	});
};
</script>

<template>
	<main class="px-8 py-8 bg-gray-100 dark:bg-gray-700">
		<h1 class="text-2xl pb-4 dark:text-gray-100">Permissions</h1>
		<div class="max-h-96">
			<FormKit type="form" submit-label="Save" @submit="submitHandler">
				<FormKit id="role" type="hidden" name="role" :value="role" />
				<div class="h-64 overflow-scroll">
					<FormKit
						id="permissions"
						v-model="rolePermissions"
						type="checkbox"
						name="permissions"
						validation="required|min:1|max:2000"
						label="Permissions"
						placeholder="Select new Rank"
						:options="List"
						error-visibility="submit"
					>
						<template #decoratorIcon="context">
							<CheckIcon v-if="context.value" class="w-5 h-5 text-white" />
						</template>
					</FormKit>
				</div>
			</FormKit>
		</div>
		<!-- {{ List }} -->
	</main>
</template>
