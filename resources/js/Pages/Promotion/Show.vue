<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import Promotion from "./Promotion.vue";
defineProps({
	promotions: Object,
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
			<div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
				<h2
					class="mx-auto max-w-2xl text-base font-semibold leading-6 text-gray-900 lg:mx-0 lg:max-w-none"
				>
					Promotion History
				</h2>
			</div>

			<div class="w-1/2 mx-auto">
				<Promotion :promotions="promotions" :filters="filters" />
			</div>
		</div>
	</MainLayout>
</template>
