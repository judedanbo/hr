<script setup lang="ts">
import { router } from "@inertiajs/vue3";
import { ref } from "vue";

const props = defineProps({
	permission: {
		type: Object,
		required: true,
	},
});

const emit = defineEmits(["submit", "close"]);
const form = ref({
	name: props.permission.name,
	guard_name: props.permission.guard_name || "web",
});

const submit = () => {
	router.put(
		route("permission.update", { permission: props.permission.id }),
		form.value,
		{
			preserveScroll: true,
			onSuccess: () => {
				emit("submit");
			},
			onError: (errors) => {
				console.error(errors);
			},
		}
	);
};
</script>
<template>
	<div
		class="flex flex-col w-full p-4 bg-white dark:bg-gray-800 rounded-lg shadow-md"
	>
		<h2 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
			Edit Permission
		</h2>
		<form @submit.prevent="submit()">
			<div class="mt-4">
				<label
					for="name"
					class="block text-sm font-medium text-gray-700 dark:text-gray-300"
				>
					Permission Name
				</label>
				<input
					id="name"
					v-model="form.name"
					type="text"
					class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 dark:bg-gray-700 dark:text-gray-200"
					required
				/>
			</div>
			<div class="mt-4">
				<label
					for="guard_name"
					class="block text-sm font-medium text-gray-700 dark:text-gray-300"
				>
					Guard Name (optional)
				</label>
				<input
					id="guard_name"
					v-model="form.guard_name"
					type="text"
					class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 dark:bg-gray-700 dark:text-gray-200"
				/>
			</div>
			<div class="mt-4">
				<button
					type="submit"
					class="inline-flex items-center px-4 py-2 bg-green-600 text-white font-semibold rounded-md shadow-sm hover:bg-green-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 dark:bg-gray-700 dark:hover:bg-gray-600"
				>
					Update Permission
				</button>
				<button
					type="button"
					class="ml-2 inline-flex items-center px-4 py-2 bg-gray-200 text-gray-700 font-semibold rounded-md shadow-sm hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500"
					@click="$emit('close')"
				>
					Cancel
				</button>
			</div>
		</form>
	</div>
</template>
