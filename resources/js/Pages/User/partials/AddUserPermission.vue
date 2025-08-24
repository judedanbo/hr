<script setup>
import { router } from "@inertiajs/vue3";
import { usePage } from "@inertiajs/vue3";
import { onMounted, ref, computed } from "vue";
import { CheckIcon } from "@heroicons/vue/24/solid";
const emit = defineEmits(["formSubmitted"]);

const props = defineProps({
	user: { type: Number, required: true },
	userPermissions: {
		type: Array,
		default: () => [],
	},
});

const List = ref([]);
const localPermissions = ref([...props.userPermissions]);

onMounted(async () => {
	const response = await axios.get(route("permission.list"));
	List.value = response.data.map((permission) => {
		return {
			value: permission.label,
			label: permission.label,
		};
	});
});

// const permissionList = computed(() => {
// 	if (typeof userRolesPermission.value.permissions === "undefined") return [];
// 	return permissions.value.map((permission) => {
// 		return {
// 			...permission,
// 			attrs: {
// 				disabled: userRolesPermission.value?.permissions?.includes(
// 					permission.value,
// 				),
// 			},
// 		};
// 	});
// });

const submitHandler = (data, node) => {
	console.log(props.user);
	router.post(route("user.add.permissions", { user: data.user }), data, {
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
				<FormKit id="user" type="hidden" name="user" :value="user" />
				<div class="h-64 overflow-scroll">
					<FormKit
						id="permissions"
						v-model="localPermissions"
						type="checkbox"
						name="permissions"
						validation="required|integer|min:1|max:2000"
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
	</main>
</template>
