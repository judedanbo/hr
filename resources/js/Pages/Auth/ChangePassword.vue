<script setup>
import BreezeButton from "@/Components/Button.vue";
import BreezeGuestLayout from "@/Layouts/Guest.vue";
import BreezeInput from "@/Components/Input.vue";
import BreezeInputError from "@/Components/InputError.vue";
import BreezeLabel from "@/Components/Label.vue";
import { Head, Link, useForm } from "@inertiajs/inertia-vue3";
import { useDark, useToggle } from "@vueuse/core";
import { SunIcon, MoonIcon } from "@heroicons/vue/24/outline";
const form = useForm({
	current_password: "",
	password: "",
	password_confirmation: "",
});

defineProps({
	previous: String,
});

const submit = () => {
	form.post(route("change-password.store"), {
		onFinish: () => form.reset("password", "password_confirmation"),
	});
};
const dark = useDark();

const toggle = useToggle(dark);
</script>

<template>
	<BreezeGuestLayout>
		<Head title="Change password" />
		<form @submit.prevent="submit">
			<div class="mt-4">
				<BreezeLabel for="current_password" value="Current password" />
				<BreezeInput
					id="current_password"
					type="password"
					class="mt-1 block w-full"
					v-model="form.current_password"
					required
					autocomplete="current_password"
				/>
				<BreezeInputError
					class="mt-2"
					:message="form.errors.current_password"
				/>
			</div>
			<div class="mt-4">
				<BreezeLabel for="password" value="New password" />
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
				<div>
					<Link
						:href="previous"
						class="underline text-sm text-gray-600 hover:text-green-900 dark:text-gray-400 dark:hover:text-gray-50"
					>
						Return back
					</Link>
					<BreezeButton
						class="ml-4"
						:class="{ 'opacity-25': form.processing }"
						:disabled="form.processing"
					>
						Change
					</BreezeButton>
				</div>
			</div>
		</form>
	</BreezeGuestLayout>
</template>
