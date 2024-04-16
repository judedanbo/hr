<template>
	<div class="px-4 sm:px-6 lg:px-8 pt-4">
		<div class="sm:flex sm:items-center">
			<div class="sm:flex-auto">
				<h1
					class="text-base font-semibold leading-6 text-gray-900 dark:text-gray-50"
				>
					Due for Promotion
				</h1>
				<p class="mt-2 text-sm text-gray-700 dark:text-gray-200">
					A list of staff who have been at the role form three years or more.
				</p>
			</div>
		</div>
		<div class="mt-8 flow-root">
			<div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
				<div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
					<div class="relative">
						<div
							v-if="selectedStaff.length > 0"
							class="absolute left-14 top-0 flex h-12 items-center space-x-3 bg-white dark:bg-gray-800 sm:left-12 px-4 rounded-md"
						>
							<button
								type="button"
								class="inline-flex items-center rounded bg-white dark:bg-gray-800 px-2 py-1 text-sm font-semibold text-gray-900 dark:text-gray-50 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-900 disabled:cursor-not-allowed disabled:opacity-30 disabled:hover:bg-white"
								:disabled="nextRank.length < 1"
								@click="promoteAll"
							>
								Promote all to {{ nextRank[0]?.label }}
							</button>
						</div>
						<table
							v-if="rankStaff?.data?.length > 0"
							class="min-w-full table-fixed divide-y divide-gray-300"
						>
							<thead>
								<tr>
									<th scope="col" class="relative px-7 sm:w-12 sm:px-6">
										<input
											type="checkbox"
											class="absolute left-4 top-1/2 -mt-2 h-4 w-4 rounded border-gray-300 text-green-600 focus:ring-green-600"
											:checked="
												indeterminate ||
												selectedStaff.length === rankStaff.total
											"
											:indeterminate="indeterminate"
											@change="
												selectedStaff = $event.target.checked
													? rankStaff.data.map((staff) => staff.id)
													: []
											"
										/>
									</th>
									<th
										scope="col"
										class="min-w-[12rem] py-3.5 pr-3 text-left text-sm font-semibold text-gray-900 dark:text-gray-50"
									>
										Name
									</th>
									<th
										scope="col"
										class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-50"
									>
										Last Promotion
									</th>
									<th
										scope="col"
										class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-50"
									>
										Current Unit
									</th>
								</tr>
							</thead>
							<tbody
								class="divide-y divide-gray-200 dark:divide-gray-600 bg-white dark:bg-gray-800"
							>
								<tr
									v-for="staff in rankStaff.data"
									:key="staff.id"
									:class="[
										selectedStaff.includes(staff.id) &&
											'bg-green-50 dark:bg-gray-950',
									]"
								>
									<td class="relative px-7 sm:w-12 sm:px-6">
										<div
											v-if="selectedStaff.includes(staff.id)"
											class="absolute inset-y-0 left-0 w-0.5 bg-green-600"
										></div>
										<input
											type="checkbox"
											class="absolute left-4 top-1/2 -mt-2 h-4 w-4 rounded border-gray-300 text-green-600 focus:ring-green-600"
											:value="staff.id"
											v-model="selectedStaff"
										/>
									</td>
									<td
										:class="[
											'whitespace-nowrap py-4 pr-3 text-sm font-medium',
											selectedStaff.includes(staff.id)
												? 'text-green-600'
												: 'text-gray-900',
										]"
									>
										<div class="flex items-center">
											<!-- <div class="h-11 w-11 flex-shrink-0">
											<img
												class="h-11 w-11 rounded-full"
												:src="person.image"
												alt=""
											/>
										</div> -->
											<div class="ml-4">
												<div
													class="font-medium text-gray-900 dark:text-gray-50"
												>
													{{ staff.name }}
												</div>
												<div class="mt-1 text-gray-500 dark:text-gray-400">
													{{ staff.staff_number }} | {{ staff.file_number }}
												</div>
											</div>
										</div>
									</td>
									<td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
										<div class="text-gray-900 dark:text-gray-50">
											{{ staff.last_promotion.start_date }}
										</div>
										<div class="mt-1 text-gray-500 dark:text-gray-400">
											{{ staff.last_promotion.remarks }}
										</div>
									</td>
									<td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
										<div class="text-gray-900 dark:text-gray-50">
											{{ staff.current_unit.name }}
										</div>
										<div class="mt-1 text-gray-500 dark:text-gray-400">
											{{ staff.current_unit.start_date }}
											{{
												staff.current_unit.remarks
													? " | " + staff.current_unit.remarks
													: ""
											}}
										</div>
									</td>
								</tr>
							</tbody>
						</table>
						<div
							v-else-if="rankStaff?.total == 0"
							class="bg-white w-full py-10 text-center rounded-lg dark:bg-gray-800"
						>
							<h2
								class="text-2xl font-bold tracking-widest dark:text-gray-100 text-gray-800"
							>
								No Staff
							</h2>
							<p
								v-if="search != ''"
								class="dark:text-gray-100 text-gray-800 tracking-widest mt-3"
							>
								No staff information matched the search criteria
							</p>
						</div>
						<div v-else class="grid place-content-center h-48">
							<Spinner
								class="w-24 h-24 animate-spin animate-duration-500"
								fill="fill-gray-100"
							/>
						</div>
					</div>
					<Pagination
						:navigation="navigation"
						@refresh-data="(page) => refreshData(page)"
					/>
				</div>
			</div>
		</div>
		<Modal :show="showPromoteAll" @close="togglePromoteAll()">
			<PromoteAllForm
				:rank="props.rank"
				@unit-selected="(data) => submitForm(data)"
				:formErrors="formErrors"
			/>
		</Modal>
	</div>
</template>

<script setup>
import { ref, computed, onMounted, onUpdated, watch } from "vue";
import { Inertia } from "@inertiajs/inertia";
import Pagination from "@/Components/Pagination.vue";
import { useNavigation } from "@/Composables/navigation";
import Modal from "@/Components/NewModal.vue";
import { useToggle } from "@vueuse/core";
import PromoteAllForm from "./PromoteAllForm.vue";
import Spinner from "@/Components/Spinner.vue";

const showPromoteAll = ref(false);
const togglePromoteAll = useToggle(showPromoteAll);
const emit = defineEmits(["formSubmitted"]);
const props = defineProps({
	rank: { type: Number, required: true },
	search: {
		type: String,
		default: "",
	},
});
const rankStaff = ref({});
const rankCategory = ref({});
const nextRank = ref({});
const formErrors = ref([]);
onMounted(async () => {
	getRankStaff();
	// console.log(rankStaff);
	const ranks = await axios.get(route("rank.category", { rank: props.rank }));
	rankCategory.value = ranks.data;
	const next = await axios.get(route("rank.next", { rank: props.rank }));
	nextRank.value = next.data;
});

const getRankStaff = async (page = null) => {
	if (page) {
		const staff = (await axios.get(page)).data;
		rankStaff.value = staff;
		selectedStaff.value = [];
		return;
	}
	const staff = (
		await axios.get(route("rank-staff.promote", { rank: props.rank }), {
			params: { search: props.search },
		})
	).data;
	rankStaff.value = staff;
	selectedStaff.value = [];
};

const navigation = computed(() => useNavigation(rankStaff.value));

const selectedStaff = ref([]);
const indeterminate = computed(
	() =>
		selectedStaff.value.length > 0 &&
		selectedStaff.value.length < rankStaff.value.total,
);
const promoteAll = () => {
	// if(nextRank.value){
	// 	togglePromoteAll()
	// }
	togglePromoteAll();
};
const submitForm = (promoteAll) => {
	const staff = selectedStaff.value;
	Inertia.post(
		route("rank-staff.promote-all"),
		{ staff, ...promoteAll },
		{
			preverseScroll: true,
			onSuccess: () => {
				togglePromoteAll();
				getRankStaff();
				emit("formSubmitted");
			},
		},
	);
};

const refreshData = (page) => {
	getRankStaff(page);
};
const searchValue = ref(props.search);
onUpdated(() => {
	searchValue.value = props.search;
});

watch(searchValue, async () => {
	await getRankStaff();
});
</script>
