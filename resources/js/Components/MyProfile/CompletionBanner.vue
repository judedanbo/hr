<script setup>
import { computed, ref, onMounted } from "vue";
import { Link, usePage } from "@inertiajs/vue3";

const page = usePage();
const auth = computed(() => page.props?.auth ?? {});

const hasPersonId = computed(() => Boolean(auth.value?.user?.person_id));
const hasPhoto = computed(() => auth.value?.has_photo === true);
const hasQuals = computed(() => (auth.value?.qualifications_count ?? 0) > 0);

// Dismissal persists across tabs and restarts for seven days. After that the
// banner reappears so users who still haven't completed their profile get
// another gentle nudge.
const dismissedKey = "my-profile.banner.dismissed-at";
const dismissalTtlMs = 7 * 24 * 60 * 60 * 1000;
const dismissed = ref(false);

onMounted(() => {
	dismissed.value = isCurrentlyDismissed();
});

function isCurrentlyDismissed() {
	try {
		const value = localStorage.getItem(dismissedKey);
		if (!value) return false;
		const dismissedAt = Number(value);
		if (!Number.isFinite(dismissedAt)) return false;
		return Date.now() - dismissedAt < dismissalTtlMs;
	} catch {
		// localStorage unavailable (private mode, etc.) — don't suppress the banner.
		return false;
	}
}

function dismiss() {
	try {
		localStorage.setItem(dismissedKey, String(Date.now()));
	} catch {
		// Swallow — UI still hides for the rest of this render.
	}
	dismissed.value = true;
}

const shouldShow = computed(
	() =>
		hasPersonId.value &&
		(!hasPhoto.value || !hasQuals.value) &&
		!dismissed.value,
);

const message = computed(() => {
	if (!hasPhoto.value && !hasQuals.value)
		return "Add your photo and first qualification.";
	if (!hasPhoto.value) return "Add your profile photo.";
	return "Add your first qualification.";
});
</script>

<template>
	<div
		v-if="shouldShow"
		data-testid="profile-completion-banner"
		class="flex items-center gap-3 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-700 px-4 py-3"
	>
		<div class="text-2xl">📝</div>
		<div class="flex-1 text-sm text-emerald-900 dark:text-emerald-100">
			<strong>Complete your profile.</strong>
			{{ message }}
		</div>
		<Link
			:href="route('my-profile.show')"
			class="inline-flex items-center rounded-lg bg-emerald-600 hover:bg-emerald-700 px-3 py-1.5 text-xs font-semibold text-white"
			>Open My Profile →</Link
		>
		<button
			type="button"
			class="text-emerald-700 dark:text-emerald-300 text-xs font-semibold hover:underline"
			@click="dismiss"
		>
			Dismiss
		</button>
	</div>
</template>
