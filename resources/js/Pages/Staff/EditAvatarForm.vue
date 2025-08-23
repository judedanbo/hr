<script setup>
import { router } from "@inertiajs/vue3";
import { ref, computed, defineEmits } from "vue";
import ImageUpload from "@/Pages/Person/partials/ImageUpload.vue";
import { FormKitMessages } from "@formkit/vue";
import { usePage } from "@inertiajs/vue3";

const page = usePage();
const errors = computed(() => page.props.value?.errors);
const emit = defineEmits(["imageUpdated", "uploadFailed"]);
const props = defineProps({
	staff: {
		type: Object,
		required: true,
	},
});

let formData = ref(null);

const submitImage = async (image) => {
	formData.value = new FormData();
	formData.value.append("image", image?.image[0]?.file);
	const avatar = await router.post(
		route("person.avatar.update", { person: props.staff.person_id }),
		formData.value,
		{
			preserveScroll: true,
			onSuccess: () => {
				// return true;
				emit("imageUpdated");
			},
			onError: (errors) => {
				emit("uploadFailed");
				// const errorNode = getNode("image");
				// const errorMsg = {
				// 	"image.image": errors.image ?? "",
				// };
				// errorNode.setErrors(errors);
				// errorNode = { errors: "there are errors" }; // TODO fix display server side image errors
			},
		},
	);
};
</script>
<template>
	<main class="bg-gray-100 dark:bg-gray-700 px-8 py-8">
		<h1 class="text-2xl dark:text-gray-200">Add/Edit Picture</h1>
		<!-- {{ staff }} -->
		<FormKit
			v-if="staff"
			id="addStaffForm"
			type="form"
			name="addStaffForm"
			submit-label="Save"
			wrapper-class="mx-auto"
			@submit="submitImage"
		>
			<FormKit id="staff_id" type="hidden" name="staff_id" :value="staff.id" />
			<ImageUpload :image-url="staff.image" />
		</FormKit>
		<div v-else class="h-96 dark:text-white grid place-items-center">
			<img src="/images/spinner.gif" alt="spinner" />
		</div>
	</main>
</template>
