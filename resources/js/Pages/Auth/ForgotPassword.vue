<script setup>
import BreezeButton from "@/Components/Button.vue";
import BreezeGuestLayout from "@/Layouts/Guest.vue";
import BreezeInput from "@/Components/Input.vue";
import BreezeInputError from "@/Components/InputError.vue";
import BreezeLabel from "@/Components/Label.vue";
import { Head, useForm } from "@inertiajs/vue3";
import { useDark, useToggle } from "@vueuse/core";
import { SunIcon, MoonIcon } from "@heroicons/vue/24/outline";

defineProps({
	status: String,
});

const form = useForm({
	email: "",
});

const submit = () => {
	form.post(route("password.email"));
};

const dark = useDark();

const toggle = useToggle(dark);
</script>

<template>
	<BreezeGuestLayout>
		<Head title="Forgot Password" />

		<div class="mb-4 text-sm text-gray-600 dark:text-gray-100">
			Forgot your password? No problem. Just let us know your email address and
			we will email you a password reset link that will allow you to choose a
			new one.
		</div>

		<div v-if="status" class="mb-4 font-medium text-sm text-green-600">
			{{ status }}
		</div>

		<form @submit.prevent="submit">
			<div>
				<BreezeLabel for="email" value="Email" />
				<BreezeInput
					id="email"
					v-model="form.email"
					type="email"
					class="mt-1 block w-full"
					required
					autofocus
					autocomplete="username"
				/>
				<BreezeInputError class="mt-2" :message="form.errors.email" />
			</div>

			<div class="flex items-center justify-between mt-4">
				<div>
					<SunIcon
						v-if="dark"
						class="w-5 h-5 rounded-full bg-white"
						@click="toggle()"
					/>
					<MoonIcon v-else class="w-5 h-5 rounded-full" @click="toggle()" />
				</div>
				<BreezeButton
					:class="{ 'opacity-25': form.processing }"
					:disabled="form.processing"
				>
					Email Password Reset Link
				</BreezeButton>
			</div>
		</form>
	</BreezeGuestLayout>
</template>
