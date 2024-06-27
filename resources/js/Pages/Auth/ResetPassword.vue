<script setup>
import BreezeButton from "@/Components/Button.vue";
import BreezeGuestLayout from "@/Layouts/Guest.vue";
import BreezeInput from "@/Components/Input.vue";
import BreezeInputError from "@/Components/InputError.vue";
import BreezeLabel from "@/Components/Label.vue";
import { useDark, useToggle } from "@vueuse/core";
import { Head, useForm } from "@inertiajs/inertia-vue3";
import { SunIcon, MoonIcon } from "@heroicons/vue/24/outline";

const props = defineProps({
	email: String,
	token: String,
});

const form = useForm({
	token: props.token,
	email: props.email,
	password: "",
	password_confirmation: "",
});

const dark = useDark();

const toggle = useToggle(dark);

const submit = () => {
	form.post(route("password.update"), {
		onFinish: () => form.reset("password", "password_confirmation"),
	});
};
</script>

<template>
	<BreezeGuestLayout>
		<Head title="Reset Password" />

		<form @submit.prevent="submit">
			<div>
				<BreezeLabel for="email" value="Email" />
				<BreezeInput
					id="email"
					type="email"
					class="mt-1 block w-full"
					v-model="form.email"
					required
					autofocus
					autocomplete="username"
				/>
				<BreezeInputError class="mt-2" :message="form.errors.email" />
			</div>

			<div class="mt-4">
				<BreezeLabel for="password" value="Password" />
				<BreezeInput
					id="password"
					type="password"
					class="mt-1 block w-full"
					v-model="form.password"
					required
					autocomplete="new-password"
				/>
				<BreezeInputError class="mt-2" :message="form.errors.password" />
			</div>

			<div class="mt-4">
				<BreezeLabel for="password_confirmation" value="Confirm Password" />
				<BreezeInput
					id="password_confirmation"
					type="password"
					class="mt-1 block w-full"
					v-model="form.password_confirmation"
					required
					autocomplete="new-password"
				/>
				<BreezeInputError
					class="mt-2"
					:message="form.errors.password_confirmation"
				/>
			</div>

			<div class="flex items-center justify-between mt-4">
				<div>
					<SunIcon
						@click="toggle()"
						v-if="dark"
						class="w-5 h-5 rounded-full bg-white"
					/>
					<MoonIcon @click="toggle()" v-else class="w-5 h-5 rounded-full" />
				</div>
				<BreezeButton
					:class="{ 'opacity-25': form.processing }"
					:disabled="form.processing"
				>
					Reset Password
				</BreezeButton>
			</div>
		</form>
	</BreezeGuestLayout>
</template>
