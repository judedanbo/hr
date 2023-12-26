<script setup>
import NoItem from "@/Components/NoItem.vue";
import MainTable from "@/Components/MainTable.vue";
import TableHead from "@/Components/TableHead.vue";
import TableBody from "@/Components/TableBody.vue";
import RowHeader from "@/Components/RowHeader.vue";
import TableData from "@/Components/TableData.vue";
import TableRow from "@/Components/TableRow.vue";
import StaffNameCard from "./StaffNameCard.vue";
import StaffEmploymentCard from "./StaffEmploymentCard.vue";
import StaffRetirementCard from "./StaffRetirementCard.vue";
import StaffCurrentRankCard from "./StaffCurrentRankCard.vue";
import StaffCurrentUnitCard from "./StaffCurrentUnitCard.vue";

const emit = defineEmits(["openStaff"]);
const props = defineProps({
	staff: {
		type: Array,
		required: true,
	},
});

const tableCols = [
	"Name",
	"Employment",
	"Retirement",
	"Current Rank",
	"Current Unit",
];
</script>

<template>
	<section class="flex flex-col mt-6 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
		<div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
			<div
				v-if="staff.length > 0"
				class="overflow-hidden border-b border-gray-200 rounded-md shadow-md"
			>
				<MainTable>
					<TableHead>
						<template v-for="(column, id) in tableCols" :key="id">
							<RowHeader>{{ column }}</RowHeader>
						</template>
					</TableHead>
					<TableBody>
						<template v-for="currentStaff in staff" :key="currentStaff.id">
							<TableRow @click="emit('openStaff', currentStaff.id)">
								<TableData>
									<StaffNameCard :staff="currentStaff" />
								</TableData>
								<TableData>
									<StaffEmploymentCard :staff="currentStaff" />
								</TableData>
								<TableData>
									<StaffRetirementCard :staff="currentStaff" />
								</TableData>
								<TableData>
									<StaffCurrentRankCard :staff="currentStaff" />
								</TableData>
								<TableData>
									<StaffCurrentUnitCard :staff="currentStaff" />
								</TableData>
							</TableRow>
						</template>
					</TableBody>
				</MainTable>
				<slot name="pagination" />
			</div>
			<NoItem v-else name="Staff" />
		</div>
	</section>
</template>
