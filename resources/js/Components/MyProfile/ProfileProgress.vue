<script setup>
import { computed, ref } from "vue";

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
	const hasValidAddress = Boolean(a && a.address_line_1 && a.city && a.country);
	return [
		{
			label: "Profile photo",
			hint: "Upload a clear headshot (JPG or PNG, up to 2 MB).",
			done: hasPhoto,
		},
		{
			label: "Qualification",
			hint: "Add at least one degree, diploma, or certificate.",
			done: hasQualification,
		},
		{
			label: "Phone number",
			hint: "Add at least one active phone contact.",
			done: hasPhone,
		},
		{
			label: "Address",
			hint: "Fill in address line 1, city, and country.",
			done: hasValidAddress,
		},
	];
});

const doneCount = computed(
	() => checkpoints.value.filter((c) => c.done).length,
);
const percent = computed(() =>
	Math.round((doneCount.value / checkpoints.value.length) * 100),
);
const isComplete = computed(() => percent.value === 100);

const isOpen = ref(false);
let closeTimer = null;

function show() {
	if (closeTimer) {
		clearTimeout(closeTimer);
		closeTimer = null;
	}
	isOpen.value = true;
}

function scheduleHide() {
	if (closeTimer) clearTimeout(closeTimer);
	closeTimer = setTimeout(() => {
		isOpen.value = false;
		closeTimer = null;
	}, 150);
}

function toggle() {
	isOpen.value = !isOpen.value;
}
</script>

<template>
	<div
		class="relative inline-block"
		@mouseenter="show"
		@mouseleave="scheduleHide"
		@focusin="show"
		@focusout="scheduleHide"
	>
		<button
			type="button"
			:aria-expanded="isOpen"
			aria-haspopup="true"
			:class="[
				'inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-semibold cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500',
				isComplete
					? 'bg-emerald-50 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200'
					: 'bg-amber-50 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200',
			]"
			@click="toggle"
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
		</button>

		<transition
			enter-active-class="transition duration-150 ease-out"
			enter-from-class="opacity-0 translate-y-1"
			enter-to-class="opacity-100 translate-y-0"
			leave-active-class="transition duration-100 ease-in"
			leave-from-class="opacity-100 translate-y-0"
			leave-to-class="opacity-0 translate-y-1"
		>
			<div
				v-if="isOpen"
				role="dialog"
				aria-label="Profile completion details"
				class="absolute right-0 z-20 mt-2 w-72 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-lg p-4"
			>
				<div class="flex items-baseline justify-between mb-3">
					<h4 class="text-sm font-bold text-gray-900 dark:text-gray-50">
						Profile completion
					</h4>
					<span
						:class="[
							'text-[11px] font-semibold',
							isComplete
								? 'text-emerald-700 dark:text-emerald-300'
								: 'text-amber-700 dark:text-amber-300',
						]"
					>
						{{ doneCount }} of {{ checkpoints.length }}
					</span>
				</div>

				<ul class="space-y-2.5">
					<li v-for="cp in checkpoints" :key="cp.label" class="flex gap-3">
						<span
							:class="[
								'mt-0.5 inline-flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full text-[11px] font-bold',
								cp.done
									? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-200'
									: 'bg-gray-100 text-gray-400 dark:bg-gray-700 dark:text-gray-500',
							]"
							:aria-hidden="true"
						>
							<span v-if="cp.done">✓</span>
							<span v-else>○</span>
						</span>
						<div class="flex-1 min-w-0">
							<p
								:class="[
									'text-sm font-semibold',
									cp.done
										? 'text-gray-900 dark:text-gray-100 line-through decoration-emerald-400/60 decoration-2'
										: 'text-gray-900 dark:text-gray-100',
								]"
							>
								{{ cp.label }}
							</p>
							<p
								v-if="!cp.done"
								class="text-xs text-gray-500 dark:text-gray-400 mt-0.5"
							>
								{{ cp.hint }}
							</p>
						</div>
					</li>
				</ul>

				<p
					v-if="isComplete"
					class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700 text-xs text-emerald-700 dark:text-emerald-300"
				>
					Everything on file — thanks!
				</p>
			</div>
		</transition>
	</div>
</template>
