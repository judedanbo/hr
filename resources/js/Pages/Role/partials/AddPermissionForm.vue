<script setup>
import NewCheckBox from "@/Components/NewCheckBox.vue";

const emit = defineEmits(["closeForm"]);
</script>
<template>
	<div
		class="flex flex-col w-full p-4 bg-white dark:bg-gray-800 rounded-lg shadow-md"
	>
		<h2 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
			Add Permission
		</h2>
		<form @submit.prevent="submit()">
			<div class="mt-4">
				<label
					for="name"
					class="block text-sm font-medium text-gray-700 dark:text-gray-300"
				>
					Find Permission
				</label>
				<input
					type="search"
					id="name"
					class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 dark:bg-gray-700 dark:text-gray-200"
					required
				/>
				<NewCheckBox />
			</div>
			<div class="mt-4">
				<button
					type="submit"
					class="inline-flex items-center px-4 py-2 bg-green-600 text-white font-semibold rounded-md shadow-sm hover:bg-green-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 dark:bg-gray-700 dark:hover:bg-gray-600"
				>
					Add Permission
				</button>
				<button
					type="button"
					@click="$emit('close')"
					class="ml-2 inline-flex items-center px-4 py-2 bg-gray-200 text-gray-700 font-semibold rounded-md shadow-sm hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500"
				>
					Cancel
				</button>
			</div>
		</form>
	</div>
</template>
