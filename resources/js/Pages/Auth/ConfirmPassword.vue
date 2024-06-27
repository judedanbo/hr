<script setup>
import BreezeButton from "@/Components/Button.vue";
import BreezeGuestLayout from "@/Layouts/Guest.vue";
import BreezeInput from "@/Components/Input.vue";
import BreezeInputError from "@/Components/InputError.vue";
import BreezeLabel from "@/Components/Label.vue";
import { Head, useForm } from "@inertiajs/inertia-vue3";
import { useDark, useToggle } from "@vueuse/core";
import { SunIcon, MoonIcon } from "@heroicons/vue/24/outline";

const form = useForm({
	password: "",
});

const submit = () => {
	form.post(route("password.confirm"), {
		onFinish: () => form.reset(),
	});
};
const dark = useDark();

const toggle = useToggle(dark);
</script>

<template>
	<BreezeGuestLayout>
		<Head title="Confirm Password" />

		<div class="mb-4 text-sm text-gray-600 dark:text-gray-50">
			This is a secure area of the application. Please confirm your password
			before continuing.
		</div>

		<form @submit.prevent="submit">
			<div>
				<BreezeLabel for="password" value="Password" />
				<BreezeInput
					id="password"
					type="password"
					class="mt-1 block w-full"
					v-model="form.password"
					required
					autocomplete="current-password"
					autofocus
				/>
				<BreezeInputError class="mt-2" :message="form.errors.password" />
			</div>

			<div class="flex justify-between mt-4">
				<div>
					<SunIcon
						@click="toggle()"
						v-if="dark"
						class="w-5 h-5 rounded-full bg-white"
					/>
					<MoonIcon @click="toggle()" v-else class="w-5 h-5 rounded-full" />
				</div>
				<BreezeButton
					class="ml-4"
					:class="{ 'opacity-25': form.processing }"
					:disabled="form.processing"
				>
					Confirm
				</BreezeButton>
			</div>
		</form>
	</BreezeGuestLayout>
</template>
