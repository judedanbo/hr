<script setup>
import { computed } from "vue";

const props = defineProps({
	person: { type: Object, required: true },
});

const rows = computed(() => {
	const r = [];
	const dobWithAge = props.person.dob
		? `${props.person.dob}${props.person.age ? ` (${props.person.age})` : ""}`
		: null;
	if (dobWithAge) r.push({ key: "Date of birth", value: dobWithAge });
	if (props.person.gender) r.push({ key: "Gender", value: props.person.gender });
	if (props.person.nationality) r.push({ key: "Nationality", value: props.person.nationality });
	if (props.person.religion) r.push({ key: "Religion", value: props.person.religion });
	if (props.person.marital_status) r.push({ key: "Marital status", value: props.person.marital_status });
	return r;
});

const identities = computed(() => props.person.identities ?? []);
</script>

<template>
	<section
		class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-sm"
	>
		<header class="flex justify-between items-center mb-3">
			<h3 class="text-sm font-bold text-gray-900 dark:text-gray-50">Personal details</h3>
			<span class="text-[11px] text-gray-400 dark:text-gray-500">HR-managed</span>
		</header>

		<dl v-if="rows.length > 0" class="divide-y divide-gray-100 dark:divide-gray-700 text-sm">
			<div v-for="row in rows" :key="row.key" class="flex justify-between py-1.5">
				<dt class="text-gray-500 dark:text-gray-400">{{ row.key }}</dt>
				<dd class="font-medium text-gray-900 dark:text-gray-100 truncate max-w-[60%] text-right">
					{{ row.value }}
				</dd>
			</div>
		</dl>
		<p v-else class="text-xs text-gray-500 dark:text-gray-400 py-1.5">No personal details on file.</p>

		<div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
			<h4 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-2">
				Identity documents
			</h4>
			<ul v-if="identities.length > 0" class="space-y-1.5">
				<li
					v-for="idRow in identities"
					:key="idRow.id"
					class="flex items-center gap-2 text-sm"
				>
					<span
						class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 whitespace-nowrap"
					>
						{{ idRow.id_type_display }}
					</span>
					<span class="font-mono text-gray-900 dark:text-gray-100 truncate">{{ idRow.id_number }}</span>
				</li>
			</ul>
			<p v-else class="text-xs italic text-gray-500 dark:text-gray-400">
				No identity document on file
			</p>
		</div>
	</section>
</template>
