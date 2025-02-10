<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import Promotion from "./Promotion.vue";
defineProps({
	promotions: Object,
	rank: Object,
	filters: Object,
});
</script>

<template>
	<MainLayout>
		<template #header>
			<h2
				class="font-semibold text-xl text-gray-800 leading-tight dark:text-gray-50"
			>
				Promotions
			</h2>
		</template>

		<div>
			<div class="w-1/2 mx-auto">
				<Promotion
					:promotions="promotions"
					:rank="rank?.name"
					:filters="filters"
				/>
			</div>
		</div>
	</MainLayout>
</template>
