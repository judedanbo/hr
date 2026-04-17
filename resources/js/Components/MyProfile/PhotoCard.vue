<script setup>
import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";
import NewModal from "@/Components/NewModal.vue";
import DeleteAvatar from "@/Pages/Staff/DeleteAvatar.vue";

const props = defineProps({
	person: { type: Object, required: true },
});

const isUploading = ref(false);
const errorMessage = ref("");
const isDragging = ref(false);
const fileInput = ref(null);
const showDeleteModal = ref(false);

const accepted = ["image/jpeg", "image/png"];
const maxBytes = 2 * 1024 * 1024;

const statusLabel = computed(() => (props.person.image ? "✓ Set" : "Not set"));
const statusClass = computed(() =>
	props.person.image
		? "bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200"
		: "bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200",
);

function openPicker() {
	fileInput.value?.click();
}

function onFileChange(event) {
	const file = event.target.files?.[0];
	if (file) {
		submit(file);
	}
}

function onDrop(event) {
	event.preventDefault();
	isDragging.value = false;
	const file = event.dataTransfer.files?.[0];
	if (file) {
		submit(file);
	}
}

function submit(file) {
	errorMessage.value = "";
	if (!accepted.includes(file.type)) {
		errorMessage.value = "Please choose a JPG or PNG image.";
		return;
	}
	if (file.size > maxBytes) {
		errorMessage.value = "Image must be 2 MB or smaller.";
		return;
	}

	const formData = new FormData();
	formData.append("image", file);

	isUploading.value = true;
	router.post(route("person.avatar.update", { person: props.person.id }), formData, {
		forceFormData: true,
		preserveScroll: true,
		onSuccess: () => {
			router.reload({ only: ["person"] });
		},
		onError: (errors) => {
			errorMessage.value = errors.image ?? "Upload failed — please try again.";
		},
		onFinish: () => {
			isUploading.value = false;
		},
	});
}

function confirmRemove() {
	showDeleteModal.value = true;
}

function remove() {
	router.delete(route("person.avatar.delete", { person: props.person.id }), {
		preserveScroll: true,
		onSuccess: () => {
			showDeleteModal.value = false;
			router.reload({ only: ["person"] });
		},
	});
}
</script>

<template>
	<section
		class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl p-5 sm:p-6 shadow-sm"
	>
		<header class="flex justify-between items-start mb-4">
			<div>
				<h2 class="text-base font-bold text-gray-900 dark:text-gray-50">Your photo</h2>
				<p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
					{{
						person.image
							? "Visible across the HR system."
							: "Help colleagues recognise you. Used across the system."
					}}
				</p>
			</div>
			<span
				class="text-[11px] font-semibold px-2.5 py-1 rounded-full"
				:class="statusClass"
			>{{ statusLabel }}</span>
		</header>

		<!-- FILLED -->
		<div v-if="person.image && !isUploading" class="flex gap-4 items-center">
			<img
				:src="person.image"
				alt="Profile photo"
				class="w-[120px] h-[120px] rounded-xl object-cover border-[3px] border-white dark:border-gray-700 shadow"
			/>
			<div class="flex-1">
				<div class="flex flex-wrap gap-2">
					<button
						type="button"
						class="inline-flex items-center rounded-lg bg-emerald-50 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-200 border border-emerald-200 dark:border-emerald-700 px-3 py-1.5 text-xs font-semibold hover:bg-emerald-100"
						@click="openPicker"
					>Change photo</button>
					<button
						type="button"
						class="inline-flex items-center rounded-lg bg-white dark:bg-gray-900 text-red-700 dark:text-red-300 border border-red-200 dark:border-red-700 px-3 py-1.5 text-xs font-semibold hover:bg-red-50 dark:hover:bg-red-900/30"
						@click="confirmRemove"
					>Remove</button>
				</div>
				<p v-if="errorMessage" class="mt-2 text-xs text-red-600 dark:text-red-400">{{ errorMessage }}</p>
			</div>
		</div>

		<!-- EMPTY / ERROR / UPLOADING -->
		<div v-else>
			<div
				:class="[
					'rounded-xl border-2 border-dashed px-6 py-9 text-center transition-colors cursor-pointer',
					isDragging
						? 'border-emerald-600 bg-emerald-50 dark:bg-emerald-900/30'
						: 'border-emerald-500 bg-emerald-50/60 hover:bg-emerald-50 dark:bg-emerald-900/20 dark:hover:bg-emerald-900/30',
					isUploading ? 'opacity-60 pointer-events-none' : '',
				]"
				@dragover.prevent="isDragging = true"
				@dragleave.prevent="isDragging = false"
				@drop="onDrop"
				@click="openPicker"
			>
				<div class="text-4xl">📷</div>
				<p class="mt-2 text-sm font-semibold text-emerald-900 dark:text-emerald-100">
					{{ isUploading ? "Uploading..." : "Drop your photo here" }}
				</p>
				<p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
					JPG or PNG, up to 2 MB · square crop works best
				</p>
				<p class="text-[11px] text-gray-500 mt-3 mb-2">or</p>
				<button
					type="button"
					class="inline-flex items-center rounded-lg bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700"
					@click.stop="openPicker"
				>Choose a file</button>
			</div>
			<p v-if="errorMessage" class="mt-3 text-xs text-red-600 dark:text-red-400">{{ errorMessage }}</p>
		</div>

		<input
			ref="fileInput"
			type="file"
			class="hidden"
			accept="image/jpeg,image/png"
			@change="onFileChange"
		/>

		<NewModal :show="showDeleteModal" @close="showDeleteModal = false">
			<DeleteAvatar
				@delete-confirmed="remove"
				@close="showDeleteModal = false"
			/>
		</NewModal>
	</section>
</template>
