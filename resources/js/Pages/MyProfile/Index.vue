<script setup>
import { Head } from "@inertiajs/vue3";
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import IdentityStrip from "@/Components/MyProfile/IdentityStrip.vue";
import PhotoCard from "@/Components/MyProfile/PhotoCard.vue";
import QualificationsCard from "@/Components/MyProfile/QualificationsCard.vue";
import ContactCard from "@/Components/MyProfile/ContactCard.vue";
import ReadOnlyKvCard from "@/Components/MyProfile/ReadOnlyKvCard.vue";
import PersonalDetailsCard from "@/Components/MyProfile/PersonalDetailsCard.vue";
import DependentsCard from "@/Components/MyProfile/DependentsCard.vue";
import { computed } from "vue";

const props = defineProps({
	person: { type: Object, required: true },
	staff: { type: Object, required: true },
	qualifications: { type: Array, default: () => [] },
	contacts: { type: Array, default: () => null },
	address: { type: Object, default: () => null },
});

const employmentRows = computed(() => {
	const currentRank =
		props.staff.ranks?.find((r) => !r.end_date) ?? props.staff.ranks?.[0];
	const currentUnit =
		props.staff.units?.find((u) => !u.end_date) ?? props.staff.units?.[0];

	const withDistance = (label, distance) => {
		if (!label) return "—";
		return distance ? `${label} · ${distance}` : label;
	};

	return [
		{ key: "Joined", value: props.staff.hire_date ?? "—" },
		{ key: "Rank", value: withDistance(currentRank?.name, currentRank?.distance) },
		{ key: "Unit", value: withDistance(currentUnit?.unit_name ?? currentUnit?.department, currentUnit?.distance) },
	];
});

</script>

<template>
	<Head title="My Profile" />
	<MainLayout>
		<main class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-6">
			<IdentityStrip
				:person="person"
				:staff="staff"
				:qualifications="qualifications"
				:contacts="contacts"
			/>

			<div class="mt-5 grid grid-cols-1 md:grid-cols-2 gap-5">
				<PhotoCard :person="person" />
				<QualificationsCard
					:qualifications="qualifications"
					:person="{ id: person.id, name: person.name }"
				/>
			</div>

			<div class="mt-5 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
				<PersonalDetailsCard :person="person" />
				<ContactCard
					:person-id="person.id"
					:contacts="contacts"
					:address="address"
				/>
				<ReadOnlyKvCard
					title="Employment"
					lock-label="HR-managed"
					:rows="employmentRows"
				/>
				<DependentsCard :dependents="staff.dependents" />
			</div>
		</main>
	</MainLayout>
</template>
