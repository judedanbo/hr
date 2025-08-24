<script setup>
import { ref, onMounted, computed } from "vue";
import { usePage } from "@inertiajs/vue3";

const page = usePage();
const errors = computed(() => page.props?.errors);
const props = defineProps({
	imageUrl: String,
});
const url = ref(null);

onMounted(() => {
	url.value = props.imageUrl ? props.imageUrl : "/images/placeholder.webp";
});
// const url = ref(props.imageUrl ?? "/images/placeholder.webp");

const imageChanged = () => {
	const file = profileImage.files[0];
	url.value = file ? URL.createObjectURL(file) : "/images/placeholder.webp";
};
</script>
<template>
	<div>
		<div class="py-4">
			<img
				:src="url"
				alt="preview profile image"
				class="w-56 h-56 mx-auto object-cover object-center rounded-full"
			/>
		</div>
		<FormKit
			id="profileImage"
			name="image"
			type="file"
			accept="image/*"
			validation="image"
			@input="imageChanged"
		>
		</FormKit>
		<div class="text-red-500 text-xs">
			{{ errors.image }}
		</div>
	</div>
</template>
