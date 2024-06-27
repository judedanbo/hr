<script setup>
import { computed } from "vue";
import BreezeButton from "@/Components/Button.vue";
import BreezeGuestLayout from "@/Layouts/Guest.vue";
import { Head, Link, useForm } from "@inertiajs/inertia-vue3";
import { useDark, useToggle } from "@vueuse/core";
import { SunIcon, MoonIcon } from "@heroicons/vue/24/outline";

const props = defineProps({
	status: String,
});

const form = useForm();

const submit = () => {
	form.post(route("verification.send"));
};

const verificationLinkSent = computed(
	() => props.status === "verification-link-sent",
);
const dark = useDark();

const toggle = useToggle(dark);
</script>

<template>
	<BreezeGuestLayout>
		<Head title="Email Verification" />

		<div class="mb-4 text-sm text-gray-600 dark:text-gray-50">
			Thanks for signing up! Before getting started, could you verify your email
			address by clicking on the link we just emailed to you? If you didn't
			receive the email, we will gladly send you another.
		</div>

		<div
			class="mb-4 font-medium text-sm text-green-600 dark:text-green-50"
			v-if="verificationLinkSent"
		>
			A new verification link has been sent to the email address you provided
			during registration.
		</div>

		<form @submit.prevent="submit">
			<div class="mt-4 flex items-center justify-between">
				<BreezeButton
					:class="{ 'opacity-25': form.processing }"
					:disabled="form.processing"
				>
					Resend Verification Email
				</BreezeButton>

				<Link
					:href="route('logout')"
					method="post"
					as="button"
					class="underline text-sm text-gray-600 dark:text-gray-50 hover:text-gray-900"
					>Log Out</Link
				>
			</div>
			<div class="mt-2">
				<SunIcon
					@click="toggle()"
					v-if="dark"
					class="w-5 h-5 rounded-full bg-white"
				/>
				<MoonIcon @click="toggle()" v-else class="w-5 h-5 rounded-full" />
			</div>
		</form>
	</BreezeGuestLayout>
</template>
