<script setup>
import { ref, computed, onMounted, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { Tab, TabGroup, TabList, TabPanel, TabPanels } from "@headlessui/vue";

const emit = defineEmits(["formSubmitted"]);

// Helper function to get today's date in YYYY-MM-DD format
const getTodayDate = () => {
	const date = new Date();
	return date.toISOString().split("T")[0];
};

const props = defineProps({
	unitId: {
		type: Number,
		required: true,
	},
	currentOffice: {
		type: Object,
		default: null,
	},
});

// Loading state
const loading = ref(true);

// Data for dropdowns
const offices = ref([]);
const districts = ref([]);
const regions = ref([]);
const officeTypes = ref([]);

// Selected tab
const selectedTab = ref(0);

// Form data for assigning existing office
const assignForm = ref({
	office_id: "",
	start_date: "",
});

// Form data for creating new office
const createForm = ref({
	name: "",
	type: "",
	district_id: "",
	start_date: "",
});

// Selected region for filtering districts
const selectedRegion = ref("");

// Filtered districts based on selected region
const filteredDistricts = computed(() => {
	if (!selectedRegion.value) {
		return districts.value;
	}
	return districts.value.filter(
		(d) => d.region_id === parseInt(selectedRegion.value),
	);
});

// Reset district when region changes
watch(selectedRegion, () => {
	createForm.value.district_id = "";
});

// Fetch dropdown data on mount
onMounted(async () => {
	// Initialize dates
	const todayStr = getTodayDate();
	assignForm.value.start_date = todayStr;
	createForm.value.start_date = todayStr;

	try {
		const [officesRes, districtsRes, regionsRes, typesRes] =
			await Promise.all([
				axios.get(route("offices.list")),
				axios.get(route("districts.list")),
				axios.get(route("regions.list")),
				axios.get(route("office-types.list")),
			]);

		offices.value = officesRes.data;
		districts.value = districtsRes.data;
		regions.value = regionsRes.data;
		officeTypes.value = typesRes.data;
	} catch (error) {
		console.error("Error loading office data:", error);
	} finally {
		loading.value = false;
	}
});

// Format office options for dropdown with location info
const officeOptions = computed(() => {
	return offices.value.map((office) => ({
		value: office.value,
		label: `${office.label} - ${office.district || "Unknown"}, ${office.region || "Unknown"}`,
	}));
});

// Format region options for dropdown
const regionOptions = computed(() => {
	return [
		{ value: "", label: "All Regions" },
		...regions.value.map((r) => ({
			value: r.value,
			label: r.label,
		})),
	];
});

// Format district options for dropdown
const districtOptions = computed(() => {
	return filteredDistricts.value.map((d) => ({
		value: d.value,
		label: d.label,
	}));
});

// Format office type options for dropdown
const typeOptions = computed(() => {
	return officeTypes.value.map((t) => ({
		value: t.value,
		label: t.label,
	}));
});

// Submit handler for assigning existing office
const submitAssign = (data, node) => {
	router.post(route("unit.office.store", { unit: props.unitId }), data, {
		preserveScroll: true,
		onSuccess: () => {
			node.reset();
			emit("formSubmitted");
		},
		onError: (errors) => {
			node.setErrors(["Error assigning office"], errors);
		},
	});
};

// Submit handler for creating new office
const submitCreate = (data, node) => {
	router.post(route("unit.office.create", { unit: props.unitId }), data, {
		preserveScroll: true,
		onSuccess: () => {
			node.reset();
			emit("formSubmitted");
		},
		onError: (errors) => {
			node.setErrors(["Error creating office"], errors);
		},
	});
};

const today = computed(() => getTodayDate());
</script>

<template>
	<main class="px-8 py-8 bg-gray-100 dark:bg-gray-700">
		<h1 class="text-2xl pb-4 dark:text-gray-100">
			{{ currentOffice ? "Change" : "Add" }} Office
		</h1>

		<div v-if="loading" class="text-center py-8">
			<div
				class="animate-spin rounded-full h-8 w-8 border-b-2 border-green-600 mx-auto"
			></div>
			<p class="mt-2 text-gray-500 dark:text-gray-400">Loading...</p>
		</div>

		<TabGroup v-else as="div" :selectedIndex="selectedTab" @change="selectedTab = $event">
			<TabList class="flex space-x-1 rounded-xl bg-gray-200 dark:bg-gray-600 p-1 mb-6">
				<Tab
					v-slot="{ selected }"
					as="template"
				>
					<button
						:class="[
							'w-full rounded-lg py-2.5 text-sm font-medium leading-5',
							'ring-white/60 ring-offset-2 ring-offset-green-400 focus:outline-none focus:ring-2',
							selected
								? 'bg-white dark:bg-gray-800 text-green-700 dark:text-green-400 shadow'
								: 'text-gray-600 dark:text-gray-300 hover:bg-white/[0.12] hover:text-gray-800 dark:hover:text-white',
						]"
					>
						Assign Existing
					</button>
				</Tab>
				<Tab
					v-slot="{ selected }"
					as="template"
				>
					<button
						:class="[
							'w-full rounded-lg py-2.5 text-sm font-medium leading-5',
							'ring-white/60 ring-offset-2 ring-offset-green-400 focus:outline-none focus:ring-2',
							selected
								? 'bg-white dark:bg-gray-800 text-green-700 dark:text-green-400 shadow'
								: 'text-gray-600 dark:text-gray-300 hover:bg-white/[0.12] hover:text-gray-800 dark:hover:text-white',
						]"
					>
						Create New
					</button>
				</Tab>
			</TabList>

			<TabPanels>
				<!-- Tab 1: Assign Existing Office -->
				<TabPanel>
					<FormKit
						v-model="assignForm"
						type="form"
						submit-label="Assign Office"
						@submit="submitAssign"
					>
						<FormKit
							id="office_id"
							type="select"
							name="office_id"
							label="Select Office"
							placeholder="Choose an office..."
							:options="officeOptions"
							validation="required"
							validation-visibility="submit"
						/>
						<FormKit
							id="start_date"
							type="date"
							name="start_date"
							label="Start Date"
							:max="today"
							validation-visibility="submit"
						/>
					</FormKit>
				</TabPanel>

				<!-- Tab 2: Create New Office -->
				<TabPanel>
					<FormKit
						v-model="createForm"
						type="form"
						submit-label="Create & Assign"
						@submit="submitCreate"
					>
						<FormKit
							id="name"
							type="text"
							name="name"
							label="Office Name"
							placeholder="Enter office name..."
							validation="required|string|length:2,150"
							validation-visibility="submit"
						/>
						<FormKit
							id="type"
							type="select"
							name="type"
							label="Office Type"
							placeholder="Select type..."
							:options="typeOptions"
							validation="required"
							validation-visibility="submit"
						/>

						<div class="mb-4">
							<label
								class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
							>
								Filter by Region (optional)
							</label>
							<select
								v-model="selectedRegion"
								class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 shadow-sm focus:border-green-500 focus:ring-green-500"
							>
								<option
									v-for="region in regionOptions"
									:key="region.value"
									:value="region.value"
								>
									{{ region.label }}
								</option>
							</select>
						</div>

						<FormKit
							id="district_id"
							type="select"
							name="district_id"
							label="District"
							placeholder="Select district..."
							:options="districtOptions"
							validation="required"
							validation-visibility="submit"
						/>
						<FormKit
							id="start_date"
							type="date"
							name="start_date"
							label="Start Date"
							:max="today"
							validation-visibility="submit"
						/>
					</FormKit>
				</TabPanel>
			</TabPanels>
		</TabGroup>
	</main>
</template>

<style scoped>
.formkit-outer {
	@apply w-full;
}
.formkit-submit {
	@apply justify-self-end;
}
.formkit-actions {
	@apply flex justify-end;
}
</style>
