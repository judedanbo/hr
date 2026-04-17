<script setup>
import { computed } from "vue";

const props = defineProps({
	person: { type: Object, required: true },
	qualifications: { type: Array, default: () => [] },
	contacts: { type: Array, default: () => null },
});

const checkpoints = computed(() => {
	const hasPhoto = Boolean(props.person?.image);
	const hasQualification = props.qualifications.length > 0;
	const emails = (props.contacts ?? []).filter(
		(c) => String(c.contact_type).toLowerCase() === "email" && !c.valid_end,
	);
	const phones = (props.contacts ?? []).filter(
		(c) => String(c.contact_type).toLowerCase() === "phone" && !c.valid_end,
	);
	const hasContacts = emails.length > 0 && phones.length > 0;
	return [hasPhoto, hasQualification, hasContacts];
});

const percent = computed(() =>
	Math.round((checkpoints.value.filter(Boolean).length / 3) * 100),
);
const isComplete = computed(() => percent.value === 100);
</script>

<template>
	<div
		:class="[
			'inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-semibold',
			isComplete
				? 'bg-emerald-50 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200'
				: 'bg-amber-50 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200',
		]"
	>
		<div
			:class="[
				'w-20 h-1.5 rounded-full overflow-hidden',
				isComplete
					? 'bg-emerald-200 dark:bg-emerald-800'
					: 'bg-amber-200 dark:bg-amber-800',
			]"
		>
			<div
				:class="[
					'h-full transition-all',
					isComplete ? 'bg-emerald-600' : 'bg-amber-500',
				]"
				:style="{ width: `${percent}%` }"
			></div>
		</div>
		<span v-if="isComplete">✓ Profile complete</span>
		<span v-else>{{ percent }}% complete</span>
	</div>
</template>
