<script setup>
import axios from "axios";
import { onMounted, ref } from "vue";

const props = defineProps({
	person_id: {
		type: Number,
		default: null,
	},
	// initials: String,
	// image: String,
});
const person = ref(null);
// const initials = ref(null);
onMounted(async () => {
	if (props.person_id != null) {
		person.value = await getAvatar();
	}
});

const getAvatar = async () => {
	const personData = await axios.get(route("person.avatar", props.person_id));
	return await personData.data;
};
</script>

<template>
	<div class="relative">
		<img
			v-if="person?.image?.image"
			class="flex-none rounded-full bg-gray-50"
			:src="'/storage/images/' + person.image.image"
			alt=""
		/>
		<div
			v-else
			class="w-8 h-8 rounded-full bg-gray-400 dark:bg-gray-100 flex justify-center items-center"
		>
			<h1
				class="text-white text-xs dark:text-gray-900 font-bold tracking-widest"
			>
				{{ person?.initials ?? "NA" }}
			</h1>
		</div>
	</div>
</template>
