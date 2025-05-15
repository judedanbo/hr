<script setup>
import { Inertia } from "@inertiajs/inertia";
import { usePage } from "@inertiajs/inertia-vue3";
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
// 				disabled: userRolesPermission.value?.permissions.includes(
// 					permission.value,
// 				),
// 			},
// 		};
// 	});
// });

const submitHandler = (data, node) => {
	console.log(props.user);
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
					<FormKit
						v-model="userPermissions"
						type="checkbox"
						name="permissions"
						id="permissions"
						validation="required|integer|min:1|max:2000"
						label="Permissions"
						placeholder="Select new Rank"
						:options="List"
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
