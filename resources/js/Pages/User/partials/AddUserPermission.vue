<script setup>
import { Inertia } from "@inertiajs/inertia";
import { onMounted, ref, computed } from "vue";
import { CheckIcon } from "@heroicons/vue/20/solid";
const emit = defineEmits(["formSubmitted"]);

const props = defineProps({
	user: { type: Number, required: true },
	// userPermissions: {
	// 	type: Array,
	// 	default: () => [],
	// },
});

import { format, addDays, subYears } from "date-fns";

const permissions = ref([]);
const userPermissions = ref([]);
const userRolesPermission = ref([]);

onMounted(async () => {
	const response = await axios.get(route("permission.list"));
	permissions.value = response.data.map((permission) => {
		return {
			value: permission.value,
			label: permission.label,
			// attrs: { disabled: props.user.permissions.includes(permission.value) },
		};
	});

	const response2 = await axios.get(
		route("user.permissions", { user: props.user }),
	);
	userPermissions.value = response2.data;
	const userRolesResponse = await axios.get(
		route("user.roles-permissions", { user: props.user }),
	);
	userRolesPermission.value = userRolesResponse.data;
});

const permissionList = computed(() => {
	if (typeof userRolesPermission.value.permissions === "undefined") return [];
	return permissions.value.map((permission) => {
		return {
			...permission,
			attrs: {
				disabled: userRolesPermission.value?.permissions.includes(
					permission.value,
				),
			},
		};
	});
});

const submitHandler = (data, node) => {
	Inertia.post(route("user.add.permissions", { user: data.user }), data, {
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
				<FormKit type="hidden" id="user" name="user" :value="user" />
				<div class="h-64 overflow-scroll">
					<!-- {{ userPermissions.permissions }} -->
					<FormKit
						v-model="userPermissions.permissions"
						type="checkbox"
						name="permissions"
						id="permissions"
						validation="required|integer|min:1|max:2000"
						label="Permissions"
						placeholder="Select new Rank"
						:options="permissionList"
						error-visibility="submit"
					>
						<template #decoratorIcon="context">
							<CheckIcon class="w-5 h-5 text-white" v-if="context.value" />
						</template>
					</FormKit>
				</div>
			</FormKit>
		</div>
	</main>
</template>
