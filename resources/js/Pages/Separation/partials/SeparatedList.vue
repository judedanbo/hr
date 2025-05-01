<script setup>
import NoItem from "@/Components/NoItem.vue";
import MainTable from "@/Components/MainTable.vue";
import TableHead from "@/Components/TableHead.vue";
import TableBody from "@/Components/TableBody.vue";
import RowHeader from "@/Components/RowHeader.vue";
import TableData from "@/Components/TableData.vue";
import TableRow from "@/Components/TableRow.vue";
import StaffNameCard from "../../Staff/partials/StaffNameCard.vue";
import StaffRetirementCard from "../../Staff/partials/StaffRetirementCard.vue";
import {
	PhoneIcon,
	AtSymbolIcon,
	MapPinIcon,
	HomeIcon,
} from "@heroicons/vue/20/solid";
// import UnitNameCard from "./UnitNameCard.vue";
defineProps({
	separated: { type: Array, required: true },
});
const emit = defineEmits(["openSeparation"]);
const tableCols = ["Name", "Ghana Card", "Contact", "Separation"];
</script>

<template>
	<section class="flex flex-col mt-6 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
		<div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
			<div
				v-if="separated.length > 0"
				class="overflow-hidden border-b border-gray-200 rounded-md shadow-md"
			>
				<MainTable>
					<TableHead>
						<template v-for="(column, id) in tableCols" :key="id">
							<RowHeader>{{ column }}</RowHeader>
						</template>
					</TableHead>
					<TableBody>
						<template v-for="staff in separated" :key="staff.id">
							<!-- {{ separated }} -->
							<TableRow @click="emit('openSeparation', staff.id)">
								<!-- {{ unit }} -->
								<TableData>
									<StaffNameCard :staff="staff" />
								</TableData>
								<TableData>
									{{ staff.ghana_card }}
								</TableData>
								<TableData class="space-y-1">
									<div v-for="contact in staff.contacts" :key="contact.id">
										<p class="text-sm">
											<PhoneIcon
												v-if="contact.type == 'Phone number'"
												class="h-4 w-4 inline-block mr-1"
											/>
											<AtSymbolIcon
												v-if="contact.type == 'Email Address'"
												class="h-4 w-4 inline-block mr-1"
											/>
											<MapPinIcon
												v-if="contact.type == 'Ghana PostGPS'"
												class="h-4 w-4 inline-block mr-1"
											/>
											<HomeIcon
												v-if="contact.type == 'Address'"
												class="h-4 w-4 inline-block mr-1"
											/>
											{{ contact.value }}
										</p>
									</div>
								</TableData>
								<TableData align="right">
									<div v-for="status in staff.statuses">
										{{ status.status }}
										<p>
											{{ status.start_date }} |
											{{ status.end_date }}
										</p>
										<p class="text-sm">
											{{ staff.note?.note }}
										</p>
									</div>
								</TableData>
							</TableRow>
						</template>
					</TableBody>
				</MainTable>
				<slot name="pagination" />
			</div>
			<NoItem v-else name="Units" />
		</div>
	</section>
</template>
