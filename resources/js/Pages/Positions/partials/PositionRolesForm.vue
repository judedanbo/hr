<script setup>
import { CheckIcon } from "@heroicons/vue/20/solid";
import { onMounted, ref, watch } from "vue";
import { router } from "@inertiajs/vue3";

const emit = defineEmits(["formSubmitted"]);

const props = defineProps({
	position: { type: Object, required: true },
});

// Vue 3.5 is strict about v-model on props, so bind a local copy.
const selectedRoles = ref([...(props.position.role_names ?? [])]);
const roles = ref([]);

watch(
	() => props.position.role_names,
	(names) => {
		selectedRoles.value = [...(names ?? [])];
	},
);

onMounted(async () => {
	const response = await axios.get(
		route("position.roles.index", { position: props.position.id }),
	);
	roles.value = response.data.available;
});

const submitHandler = (data, node) => {
	router.put(
		route("position.roles.sync", { position: props.position.id }),
		{ roles: selectedRoles.value },
		{
			preserveScroll: true,
			onSuccess: () => {
				emit("formSubmitted");
			},
			onError: (errors) => {
				node.setErrors([""], errors);
			},
		},
	);
};
</script>

<template>
	<main class="px-8 py-8 bg-gray-100 dark:bg-gray-700">
		<h1 class="text-2xl dark:text-gray-100">Roles granted by this position</h1>
		<p class="pb-4 pt-1 text-sm text-gray-600 dark:text-gray-300">
			Whoever currently holds
			<span class="font-semibold">{{ position.name }}</span> is given these
			roles. Ending the position takes them back, unless they were assigned by
			hand.
		</p>
		<FormKit type="form" submit-label="Save" @submit="submitHandler">
			<FormKit
				id="roles"
				v-model="selectedRoles"
				type="checkbox"
				name="roles"
				label="Roles"
				:options="roles"
				error-visibility="submit"
			>
				<template #decoratorIcon="context">
					<CheckIcon v-if="context.value" class="w-5 h-5 text-white" />
				</template>
			</FormKit>
		</FormKit>
	</main>
</template>

<style>
.formkit-decorator {
	@apply peer-checked:bg-green-500;
}
</style>
