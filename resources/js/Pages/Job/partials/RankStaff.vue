<template>
	<div class="px-4 sm:px-6 lg:px-8 pt-4">
		<div class="sm:flex sm:items-center">
			<div class="sm:flex-auto">
				<h1
					class="text-base font-semibold leading-6 text-gray-900 dark:text-gray-50"
				>
					Current staff
				</h1>
				<p class="mt-2 text-sm text-gray-700 dark:text-gray-200">
					A list of all current staff including their name, title, id and role.
				</p>
			</div>
		</div>
		<div class="mt-8 flow-root">
			<div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
				<div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
					<div class="relative">
						<table
							v-if="rankStaff?.total > 0"
							class="min-w-full table-fixed divide-y divide-gray-300"
						>
							<thead>
								<tr>
									<th
										scope="col"
										class="min-w-[12rem] py-3.5 px-8 text-left text-sm font-semibold text-gray-900 dark:text-gray-50"
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
									:key="staff.staff_number"
									:class="[
										selectedStaff.includes(staff.id) &&
											'bg-green-50 dark:bg-gray-950',
									]"
									class="cursor-pointer"
									@click="showStaff(staff.id)"
								>
									<td
										:class="[
											'whitespace-nowrap py-4 pr-3 text-sm font-medium',
											selectedStaff.includes(staff.id)
												? 'text-green-600'
												: 'text-gray-900',
										]"
									>
										<div class="flex items-center">
											<div class="ml-8">
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
								fill="fill-green-500 dark:fill-gray-100"
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
import { search } from "@formkit/icons";
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
const searchValue = ref(props.search);
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

onUpdated(async () => {
	searchValue.value = props.search;
});

watch(searchValue, () => {
	getRankStaff();
});

const getRankStaff = async (page = null) => {
	if (page) {
		// const staff = useSearch(props.search, route(page));

		const staff = (
			await axios.get(page, {
				params: { search: props.search },
			})
		).data;
		rankStaff.value = staff;
		selectedStaff.value = [];
		return;
	}
	// const staff = useSearch(
	// 	props.search,
	// 	route("rank-staff.index", { rank: props.rank }),
	// ).data;

	const staff = (
		await axios.get(route("rank-staff.index", { rank: props.rank }), {
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
	console.log(promoteAll);
	Inertia.post(
		route("rank-staff.promote"),
		{ staff, ...promoteAll },
		{
			preverseScroll: true,
			onSuccess: () => {
				togglePromoteAll();
				getRankStaff();
				emit("formSubmitted");
			},
			onError: (errors) => {
				// node.setErrors(["there are errors"], errors);
				// console.log(errors);
				// formErrors.value = {...errors};
			},
		},
	);
	// togglePromoteAll();
};

const refreshData = (page) => {
	getRankStaff(page);
};

const showStaff = (staff) => {
	Inertia.visit(route("staff.show", { staff: staff }));
};
</script>
