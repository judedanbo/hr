<script setup>
import MainTable from "@/Components/MainTable.vue";
import TableHead from "@/Components/TableHead.vue";
import TableBody from "@/Components/TableBody.vue";
import NoItem from "@/Components/NoItem.vue";

defineProps({
	// Whether there is anything to render; when false the empty state shows
	// in place of the table.
	hasItems: { type: Boolean, default: true },
	// Label used by the default empty state, e.g. "Roles".
	name: { type: String, default: "items" },
});
</script>
<template>
	<div
		class="overflow-hidden rounded-2xl border border-green-200/60 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800"
	>
		<template v-if="hasItems">
			<div class="overflow-x-auto">
				<MainTable>
					<TableHead>
						<slot name="head" />
					</TableHead>
					<TableBody>
						<slot name="body" />
					</TableBody>
				</MainTable>
			</div>
			<slot name="footer" />
		</template>
		<slot v-else name="empty">
			<NoItem :name="name" />
		</slot>
	</div>
</template>
