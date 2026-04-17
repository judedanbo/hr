<script setup>
import { ref, computed, onMounted } from "vue";
import { router } from "@inertiajs/vue3";
import { useToggle } from "@vueuse/core";
import NewModal from "@/Components/NewModal.vue";

const props = defineProps({
	personId: { type: Number, required: true },
	address: { type: Object, default: () => null },
});

const openEdit = ref(false);
const toggleEdit = useToggle(openEdit);

const openChange = ref(false);
const toggleChange = useToggle(openChange);

const countries = ref([]);
onMounted(async () => {
	try {
		const response = await axios.get(route("country.index"));
		countries.value = response.data;
	} catch {
		countries.value = [];
	}
});

const addressDisplay = computed(() => {
	if (!props.address) return null;
	return [
		props.address.address_line_1,
		props.address.address_line_2,
		props.address.city,
		props.address.region,
		props.address.country,
		props.address.post_code,
	]
		.filter(Boolean)
		.join(", ");
});

function blankForm() {
	return {
		address_line_1: "",
		address_line_2: "",
		city: "",
		region: "",
		country: "",
		post_code: "",
	};
}

const editForm = ref(blankForm());
const editErrors = ref({});

const changeForm = ref(blankForm());
const changeErrors = ref({});

function openEditModal() {
	editErrors.value = {};
	editForm.value = props.address
		? {
				address_line_1: props.address.address_line_1 ?? "",
				address_line_2: props.address.address_line_2 ?? "",
				city: props.address.city ?? "",
				region: props.address.region ?? "",
				country: props.address.country ?? "",
				post_code: props.address.post_code ?? "",
			}
		: blankForm();
	toggleEdit();
}

function openChangeModal() {
	changeErrors.value = {};
	changeForm.value = blankForm();
	toggleChange();
}

function openAddModal() {
	editErrors.value = {};
	editForm.value = blankForm();
	toggleEdit();
}

function saveAddress() {
	editErrors.value = {};

	if (props.address) {
		router.patch(
			route("person.address.update", {
				person: props.personId,
				address: props.address.id,
			}),
			editForm.value,
			{
				preserveScroll: true,
				onSuccess: () => {
					openEdit.value = false;
					router.reload({ only: ["address"] });
				},
				onError: (errors) => {
					editErrors.value = errors;
				},
			},
		);
	} else {
		router.post(
			route("person.address.create", { person: props.personId }),
			editForm.value,
			{
				preserveScroll: true,
				onSuccess: () => {
					openEdit.value = false;
					router.reload({ only: ["address"] });
				},
				onError: (errors) => {
					editErrors.value = errors;
				},
			},
		);
	}
}

function changeAddress() {
	changeErrors.value = {};

	router.post(
		route("person.address.change", { person: props.personId }),
		changeForm.value,
		{
			preserveScroll: true,
			onSuccess: () => {
				openChange.value = false;
				router.reload({ only: ["address"] });
			},
			onError: (errors) => {
				changeErrors.value = errors;
			},
		},
	);
}
</script>

<template>
	<section
		class="bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-sm"
	>
		<header class="flex justify-between items-center mb-3">
			<h3 class="text-sm font-bold text-gray-900 dark:text-gray-50">Address</h3>
			<div class="flex items-center gap-3">
				<template v-if="address">
					<button
						type="button"
						class="text-[11px] font-semibold text-emerald-700 dark:text-emerald-300 hover:underline"
						@click="openEditModal()"
					>
						Edit
					</button>
					<button
						type="button"
						class="text-[11px] font-semibold text-blue-600 dark:text-blue-400 hover:underline"
						@click="openChangeModal()"
					>
						Change
					</button>
				</template>
				<button
					v-else
					type="button"
					class="text-[11px] font-semibold text-emerald-700 dark:text-emerald-300 hover:underline"
					@click="openAddModal()"
				>
					Add address
				</button>
			</div>
		</header>

		<div v-if="addressDisplay" class="text-sm text-gray-900 dark:text-gray-100">
			<p class="leading-relaxed">{{ addressDisplay }}</p>
		</div>
		<p v-else class="text-sm text-gray-500 dark:text-gray-400 py-1">
			No address on file.
		</p>

		<!-- Edit / Add modal -->
		<NewModal :show="openEdit" @close="toggleEdit()">
			<div class="px-6 py-6">
				<h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">
					{{ address ? "Edit address" : "Add address" }}
				</h2>

				<div
					v-if="Object.keys(editErrors).length"
					class="mb-3 rounded-md bg-red-50 dark:bg-red-900/20 px-4 py-2"
				>
					<ul
						class="list-disc list-inside text-sm text-red-700 dark:text-red-300 space-y-1"
					>
						<li v-for="(msg, field) in editErrors" :key="field">{{ msg }}</li>
					</ul>
				</div>

				<form class="space-y-3" @submit.prevent="saveAddress">
					<div>
						<label
							for="edit_address_line_1"
							class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
						>
							Address line 1 <span class="text-red-500">*</span>
						</label>
						<input
							id="edit_address_line_1"
							v-model="editForm.address_line_1"
							type="text"
							required
							class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-sm text-gray-900 dark:text-gray-100 px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-emerald-500"
							:class="{ 'border-red-500': editErrors.address_line_1 }"
						/>
						<p
							v-if="editErrors.address_line_1"
							class="mt-1 text-xs text-red-600 dark:text-red-400"
						>
							{{ editErrors.address_line_1 }}
						</p>
					</div>

					<div>
						<label
							for="edit_address_line_2"
							class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
						>
							Address line 2
						</label>
						<input
							id="edit_address_line_2"
							v-model="editForm.address_line_2"
							type="text"
							class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-sm text-gray-900 dark:text-gray-100 px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-emerald-500"
						/>
					</div>

					<div class="grid grid-cols-2 gap-3">
						<div>
							<label
								for="edit_city"
								class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
							>
								City <span class="text-red-500">*</span>
							</label>
							<input
								id="edit_city"
								v-model="editForm.city"
								type="text"
								required
								class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-sm text-gray-900 dark:text-gray-100 px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-emerald-500"
								:class="{ 'border-red-500': editErrors.city }"
							/>
							<p
								v-if="editErrors.city"
								class="mt-1 text-xs text-red-600 dark:text-red-400"
							>
								{{ editErrors.city }}
							</p>
						</div>
						<div>
							<label
								for="edit_post_code"
								class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
							>
								Post code
							</label>
							<input
								id="edit_post_code"
								v-model="editForm.post_code"
								type="text"
								class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-sm text-gray-900 dark:text-gray-100 px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-emerald-500"
							/>
						</div>
					</div>

					<div>
						<label
							for="edit_region"
							class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
						>
							Region
						</label>
						<input
							id="edit_region"
							v-model="editForm.region"
							type="text"
							class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-sm text-gray-900 dark:text-gray-100 px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-emerald-500"
						/>
					</div>

					<div>
						<label
							for="edit_country"
							class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
						>
							Country <span class="text-red-500">*</span>
						</label>
						<select
							id="edit_country"
							v-model="editForm.country"
							required
							class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-sm text-gray-900 dark:text-gray-100 px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-emerald-500"
							:class="{ 'border-red-500': editErrors.country }"
						>
							<option value="" disabled>Select country</option>
							<option
								v-for="c in countries"
								:key="c.value ?? c"
								:value="c.value ?? c"
							>
								{{ c.label ?? c }}
							</option>
						</select>
						<p
							v-if="editErrors.country"
							class="mt-1 text-xs text-red-600 dark:text-red-400"
						>
							{{ editErrors.country }}
						</p>
					</div>

					<div class="mt-5 flex justify-end gap-2 pt-4 border-t border-gray-100 dark:border-gray-700">
						<button
							type="button"
							class="inline-flex items-center rounded-lg px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
							@click="toggleEdit()"
						>
							Cancel
						</button>
						<button
							type="submit"
							class="inline-flex items-center rounded-lg bg-emerald-600 hover:bg-emerald-700 px-4 py-2 text-sm font-bold text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 dark:focus:ring-offset-gray-800 transition-colors"
						>
							Save changes
						</button>
					</div>
				</form>
			</div>
		</NewModal>

		<!-- Change address modal -->
		<NewModal :show="openChange" @close="toggleChange()">
			<div class="px-6 py-6">
				<h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-1">
					Change address
				</h2>
				<p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
					The current address will be archived and replaced with this new one.
				</p>

				<div
					v-if="Object.keys(changeErrors).length"
					class="mb-3 rounded-md bg-red-50 dark:bg-red-900/20 px-4 py-2"
				>
					<ul
						class="list-disc list-inside text-sm text-red-700 dark:text-red-300 space-y-1"
					>
						<li v-for="(msg, field) in changeErrors" :key="field">{{ msg }}</li>
					</ul>
				</div>

				<form class="space-y-3" @submit.prevent="changeAddress">
					<div>
						<label
							for="change_address_line_1"
							class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
						>
							Address line 1 <span class="text-red-500">*</span>
						</label>
						<input
							id="change_address_line_1"
							v-model="changeForm.address_line_1"
							type="text"
							required
							class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-sm text-gray-900 dark:text-gray-100 px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-500"
							:class="{ 'border-red-500': changeErrors.address_line_1 }"
						/>
						<p
							v-if="changeErrors.address_line_1"
							class="mt-1 text-xs text-red-600 dark:text-red-400"
						>
							{{ changeErrors.address_line_1 }}
						</p>
					</div>

					<div>
						<label
							for="change_address_line_2"
							class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
						>
							Address line 2
						</label>
						<input
							id="change_address_line_2"
							v-model="changeForm.address_line_2"
							type="text"
							class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-sm text-gray-900 dark:text-gray-100 px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-500"
						/>
					</div>

					<div class="grid grid-cols-2 gap-3">
						<div>
							<label
								for="change_city"
								class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
							>
								City <span class="text-red-500">*</span>
							</label>
							<input
								id="change_city"
								v-model="changeForm.city"
								type="text"
								required
								class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-sm text-gray-900 dark:text-gray-100 px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-500"
								:class="{ 'border-red-500': changeErrors.city }"
							/>
							<p
								v-if="changeErrors.city"
								class="mt-1 text-xs text-red-600 dark:text-red-400"
							>
								{{ changeErrors.city }}
							</p>
						</div>
						<div>
							<label
								for="change_post_code"
								class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
							>
								Post code
							</label>
							<input
								id="change_post_code"
								v-model="changeForm.post_code"
								type="text"
								class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-sm text-gray-900 dark:text-gray-100 px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-500"
							/>
						</div>
					</div>

					<div>
						<label
							for="change_region"
							class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
						>
							Region
						</label>
						<input
							id="change_region"
							v-model="changeForm.region"
							type="text"
							class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-sm text-gray-900 dark:text-gray-100 px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-500"
						/>
					</div>

					<div>
						<label
							for="change_country"
							class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
						>
							Country <span class="text-red-500">*</span>
						</label>
						<select
							id="change_country"
							v-model="changeForm.country"
							required
							class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-sm text-gray-900 dark:text-gray-100 px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-500"
							:class="{ 'border-red-500': changeErrors.country }"
						>
							<option value="" disabled>Select country</option>
							<option
								v-for="c in countries"
								:key="c.value ?? c"
								:value="c.value ?? c"
							>
								{{ c.label ?? c }}
							</option>
						</select>
						<p
							v-if="changeErrors.country"
							class="mt-1 text-xs text-red-600 dark:text-red-400"
						>
							{{ changeErrors.country }}
						</p>
					</div>

					<div class="mt-5 flex justify-end gap-2 pt-4 border-t border-gray-100 dark:border-gray-700">
						<button
							type="button"
							class="inline-flex items-center rounded-lg px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
							@click="toggleChange()"
						>
							Cancel
						</button>
						<button
							type="submit"
							class="inline-flex items-center rounded-lg bg-blue-600 hover:bg-blue-700 px-4 py-2 text-sm font-bold text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-gray-800 transition-colors"
						>
							Save as new address
						</button>
					</div>
				</form>
			</div>
		</NewModal>
	</section>
</template>
