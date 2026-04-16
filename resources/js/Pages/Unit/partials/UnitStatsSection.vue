<script setup>
import { computed } from "vue";
import {
	UsersIcon,
	UserGroupIcon,
	Square3Stack3DIcon,
} from "@heroicons/vue/24/outline";
import StatCard from "@/Components/StatCard.vue";

const props = defineProps({
	stats: {
		type: Object,
		required: true,
	},
});

const cards = computed(() => [
	{
		id: "total-staff",
		label: "Total Staff",
		value: props.stats?.total ?? 0,
		icon: UsersIcon,
		accent: "emerald",
	},
	{
		id: "male-staff",
		label: "Male Staff",
		value: props.stats?.male ?? 0,
		icon: UserGroupIcon,
		accent: "indigo",
	},
	{
		id: "female-staff",
		label: "Female Staff",
		value: props.stats?.female ?? 0,
		icon: UserGroupIcon,
		accent: "pink",
	},
	{
		id: "sub-units",
		label: "Sub-Units",
		value: props.stats?.direct_subs ?? 0,
		icon: Square3Stack3DIcon,
		accent: "slate",
		secondary:
			props.stats?.total_descendants > props.stats?.direct_subs
				? `${props.stats.total_descendants} nested`
				: null,
	},
]);
</script>

<template>
	<section>
		<h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
			Overview
		</h2>
		<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
			<StatCard
				v-for="card in cards"
				:key="card.id"
				:label="card.label"
				:value="card.value"
				:icon="card.icon"
				:accent="card.accent"
				:secondary="card.secondary"
			/>
		</div>
	</section>
</template>
