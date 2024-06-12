<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head } from "@inertiajs/inertia-vue3";
import BreadCrumpVue from "@/Components/BreadCrump.vue";

defineProps({ staff: Object });
</script>
<template>
	<MainLayout>
		<Head title="Staff" />

		<main class="max-w-7xl mx-auto sm:px-6 lg:px-8">
			<BreadCrumpVue :links="BreadCrumpLinks" />
			<div class="overflow-hidden shadow-sm sm:rounded-lg px-6 border-gray-200">
				{{ staff }}
			</div>
		</main>
	</MainLayout>
</template>
