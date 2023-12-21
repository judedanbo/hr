<script setup>
import { DocumentArrowUpIcon } from "@heroicons/vue/20/solid";
const props = defineProps({
	url: {
		type: String,
		default: null,
	},
	type: {
		type: String,
		default: null,
	},
});
</script>
<template>
	<object v-if="url">
		<template v-if="type == 'application/pdf'">
			<embed :src="url" :type="type" width="100%" height="500px" />
		</template>
		<template v-else>
			<img
				:src="url"
				alt="preview not available"
				class="mx-auto object-cover object-center"
			/>
		</template>
	</object>
	<template v-else>
		<DocumentArrowUpIcon class="text-gray-300 dark:text-gray-500" />
	</template>
</template>
