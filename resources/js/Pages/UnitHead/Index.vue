<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, router } from "@inertiajs/vue3";
import { computed } from "vue";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import Pagination from "@/Components/Pagination.vue";
import TableHeader from "@/Components/TableHeader.vue";
import MainTable from "@/Components/MainTable.vue";
import TableHead from "@/Components/TableHead.vue";
import TableBody from "@/Components/TableBody.vue";
import RowHeader from "@/Components/RowHeader.vue";
import TableData from "@/Components/TableData.vue";
import TableRow from "@/Components/TableRow.vue";
import { useNavigation } from "@/Composables/navigation";
import { useSearch } from "@/Composables/search";

const props = defineProps({
	units: { type: Object, required: true },
	staffOptions: { type: Array, default: () => [] },
	filters: { type: Object, default: () => ({}) },
});

const navigation = computed(() => useNavigation(props.units));
const search = (value) => useSearch(value, route("unit-head.index"));

const setHead = (unit, value) => {
	router.patch(
		route("unit-head.update", { unit: unit.id }),
		{ head_staff_id: value || null },
		{ preserveScroll: true },
	);
};

const links = [{ name: "Unit Heads", url: "" }];
const tableCols = ["Unit", "Type", "Approving head"];
</script>

<template>
	<MainLayout>
		<Head title="Unit Heads" />
		<main class="max-w-7xl mx-auto sm:px-6 lg:px-8">
			<BreadCrumpVue :links="links" />
			<div
				class="overflow-hidden shadow-sm sm:rounded-lg px-6 border-b border-gray-200"
			>
				<TableHeader
					title="Unit Heads"
					:total="units.total"
					:search="filters.search"
					action-text=""
					@search-entered="(value) => search(value)"
				/>
				<section
					class="flex flex-col mt-6 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8"
				>
					<div
						class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8"
					>
						<div
							class="overflow-hidden border-b border-gray-200 rounded-md shadow-md"
						>
							<MainTable>
								<TableHead>
									<template v-for="(column, id) in tableCols" :key="id">
										<RowHeader>{{ column }}</RowHeader>
									</template>
								</TableHead>
								<TableBody>
									<template v-for="unit in units.data" :key="unit.id">
										<TableRow>
											<TableData>{{ unit.name }}</TableData>
											<TableData>{{ unit.type }}</TableData>
											<TableData>
												<select
													class="rounded-md border-gray-300 text-sm dark:bg-gray-600 dark:text-gray-100"
													:value="unit.head_staff_id || ''"
													@change="setHead(unit, $event.target.value)"
												>
													<option value="">— none —</option>
													<option
														v-for="opt in staffOptions"
														:key="opt.value"
														:value="opt.value"
													>
														{{ opt.label }}
													</option>
												</select>
											</TableData>
										</TableRow>
									</template>
								</TableBody>
							</MainTable>
							<Pagination :navigation="navigation" />
						</div>
					</div>
				</section>
			</div>
		</main>
	</MainLayout>
</template>
