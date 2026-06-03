<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head } from "@inertiajs/vue3";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import MainTable from "@/Components/MainTable.vue";
import TableHead from "@/Components/TableHead.vue";
import TableBody from "@/Components/TableBody.vue";
import RowHeader from "@/Components/RowHeader.vue";
import TableData from "@/Components/TableData.vue";
import TableRow from "@/Components/TableRow.vue";

defineProps({
	plan: { type: Object, required: true },
});

const links = [
	{ name: "All Leave Plans", url: "/leave-plans" },
	{ name: "Plan", url: "" },
];
const tableCols = ["Leave type", "Start", "End", "Days", "Note"];
</script>

<template>
	<MainLayout>
		<Head title="Leave Plan" />
		<main class="max-w-7xl mx-auto sm:px-6 lg:px-8">
			<BreadCrumpVue :links="links" />
			<div class="mt-6 rounded-md bg-white dark:bg-gray-800 p-6 shadow-sm">
				<h1 class="text-xl font-semibold text-gray-800 dark:text-gray-100">
					{{ plan.staff }} — {{ plan.year }}
				</h1>
				<p class="text-sm text-gray-500 dark:text-gray-300">
					{{ plan.status }} · submitted {{ plan.submitted_at }}
				</p>
				<section class="mt-4 overflow-x-auto">
					<MainTable>
						<TableHead>
							<template v-for="(column, id) in tableCols" :key="id">
								<RowHeader>{{ column }}</RowHeader>
							</template>
						</TableHead>
						<TableBody>
							<template v-for="item in plan.items" :key="item.id">
								<TableRow>
									<TableData>{{ item.leave_type }}</TableData>
									<TableData>{{ item.start_date }}</TableData>
									<TableData>{{ item.end_date }}</TableData>
									<TableData>{{ item.proposed_days }}</TableData>
									<TableData>{{ item.note || "—" }}</TableData>
								</TableRow>
							</template>
						</TableBody>
					</MainTable>
				</section>
			</div>
		</main>
	</MainLayout>
</template>
