<script setup>
import { Head } from "@inertiajs/vue3";
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import PageShell from "@/Components/UI/PageShell.vue";
import PageHeader from "@/Components/UI/PageHeader.vue";
import IntegrityCheckCard from "@/Components/IntegrityCheckCard.vue";

defineProps({
	checks: {
		type: Array,
		required: true,
	},
});

const breadcrumbLinks = [{ name: "Data Integrity", url: null }];
</script>

<template>
	<MainLayout>
		<Head title="Data Integrity Checks" />
		<PageShell>
			<PageHeader
				title="Data Integrity"
				description="Monitor and fix data integrity issues across the system."
				:breadcrumbs="breadcrumbLinks"
			/>

			<div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
				<IntegrityCheckCard
					v-for="check in checks"
					:key="check.id"
					:title="check.title"
					:description="check.description"
					:count="check.count"
					:severity="check.severity"
					:href="check.route"
				/>
			</div>
		</PageShell>
	</MainLayout>
</template>
