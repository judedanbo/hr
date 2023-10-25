<script setup>
import { DocumentArrowUpIcon } from "@heroicons/vue/20/solid";
const props = defineProps({
	url: {
		type: String,
		default: "",
	},
	type: {
		type: String,
		default: "",
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
				alt="preview document"
				class="mx-auto object-cover object-center"
			/>
		</template>
	</object>
	<template v-else>
		<DocumentArrowUpIcon class="text-gray-300 dark:text-gray-500" />
	</template>
</template>
