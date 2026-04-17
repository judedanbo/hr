<script setup>
import { computed } from "vue";

// ContactTypeEnum: Phone = 2 (backed int enum serialized as integer).
const CONTACT_TYPE_PHONE = 2;

const props = defineProps({
	person: { type: Object, required: true },
	qualifications: { type: Array, default: () => [] },
	contacts: { type: Array, default: () => null },
	address: { type: Object, default: () => null },
});

const checkpoints = computed(() => {
	const hasPhoto = Boolean(props.person?.image);
	const hasQualification = props.qualifications.length > 0;
	const hasPhone = (props.contacts ?? []).some(
		(c) => c.contact_type === CONTACT_TYPE_PHONE && !c.valid_end,
	);
	const a = props.address;
	const hasValidAddress = Boolean(
		a && a.address_line_1 && a.city && a.country,
	);
	return [hasPhoto, hasQualification, hasPhone, hasValidAddress];
});

const percent = computed(() =>
	Math.round((checkpoints.value.filter(Boolean).length / 4) * 100),
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
