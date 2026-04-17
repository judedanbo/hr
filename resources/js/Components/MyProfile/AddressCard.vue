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

const form = ref({
	address_line_1: "",
	address_line_2: "",
	city: "",
	region: "",
	country: "",
	post_code: "",
});

function openEditModal() {
	if (props.address) {
		form.value = {
			address_line_1: props.address.address_line_1 ?? "",
			address_line_2: props.address.address_line_2 ?? "",
			city: props.address.city ?? "",
			region: props.address.region ?? "",
			country: props.address.country ?? "",
			post_code: props.address.post_code ?? "",
		};
	} else {
		form.value = {
			address_line_1: "",
			address_line_2: "",
			city: "",
			region: "",
			country: "",
			post_code: "",
		};
	}
	toggleEdit();
}

function saveAddress() {
	const data = { ...form.value };

	if (props.address) {
		router.patch(
			route("person.address.update", {
				person: props.personId,
				address: props.address.id,
			}),
			data,
			{
				preserveScroll: true,
				onSuccess: () => {
					toggleEdit();
					router.reload({ only: ["address"] });
				},
			},
		);
	} else {
		router.post(
			route("person.address.create", { person: props.personId }),
			data,
			{
				preserveScroll: true,
				onSuccess: () => {
					toggleEdit();
					router.reload({ only: ["address"] });
				},
			},
		);
	}
}
</script>

<template>
	<section
		class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-sm"
	>
		<header class="flex justify-between items-center mb-3">
			<h3 class="text-sm font-bold text-gray-900 dark:text-gray-50">Address</h3>
			<button
				type="button"
				class="text-[11px] font-semibold text-emerald-700 dark:text-emerald-300 hover:underline"
				@click="openEditModal()"
			>
				{{ address ? "Edit" : "Add address" }}
			</button>
		</header>

		<div v-if="addressDisplay" class="text-sm text-gray-900 dark:text-gray-100">
			<p class="leading-relaxed">{{ addressDisplay }}</p>
		</div>
		<p v-else class="text-sm text-gray-500 dark:text-gray-400 py-1">
			No address on file.
		</p>

		<NewModal :show="openEdit" @close="toggleEdit()">
			<div class="px-6 py-6">
				<h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">
					{{ address ? "Edit address" : "Add address" }}
				</h2>

				<form class="space-y-3" @submit.prevent="saveAddress">
					<div>
						<label
							for="address_line_1"
							class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
						>
							Address line 1 <span class="text-red-500">*</span>
						</label>
						<input
							id="address_line_1"
							v-model="form.address_line_1"
							type="text"
							required
							class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-sm text-gray-900 dark:text-gray-100 px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-emerald-500"
						/>
					</div>

					<div>
						<label
							for="address_line_2"
							class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
						>
							Address line 2
						</label>
						<input
							id="address_line_2"
							v-model="form.address_line_2"
							type="text"
							class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-sm text-gray-900 dark:text-gray-100 px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-emerald-500"
						/>
					</div>

					<div class="grid grid-cols-2 gap-3">
						<div>
							<label
								for="city"
								class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
							>
								City <span class="text-red-500">*</span>
							</label>
							<input
								id="city"
								v-model="form.city"
								type="text"
								required
								class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-sm text-gray-900 dark:text-gray-100 px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-emerald-500"
							/>
						</div>
						<div>
							<label
								for="post_code"
								class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
							>
								Post code
							</label>
							<input
								id="post_code"
								v-model="form.post_code"
								type="text"
								class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-sm text-gray-900 dark:text-gray-100 px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-emerald-500"
							/>
						</div>
					</div>

					<div>
						<label
							for="region"
							class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
						>
							Region
						</label>
						<input
							id="region"
							v-model="form.region"
							type="text"
							class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-sm text-gray-900 dark:text-gray-100 px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-emerald-500"
						/>
					</div>

					<div>
						<label
							for="country"
							class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
						>
							Country <span class="text-red-500">*</span>
						</label>
						<select
							id="country"
							v-model="form.country"
							required
							class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-sm text-gray-900 dark:text-gray-100 px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-emerald-500"
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
					</div>

					<div class="mt-4 flex justify-end gap-3">
						<button
							type="button"
							class="text-sm text-gray-600 dark:text-gray-400 hover:underline"
							@click="toggleEdit()"
						>
							Cancel
						</button>
						<button
							type="submit"
							class="text-sm font-semibold text-emerald-700 dark:text-emerald-300 hover:underline"
						>
							Save
						</button>
					</div>
				</form>
			</div>
		</NewModal>
	</section>
</template>
