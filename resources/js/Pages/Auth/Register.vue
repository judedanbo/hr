<script setup>
import BreezeButton from "@/Components/Button.vue";
import BreezeGuestLayout from "@/Layouts/Guest.vue";
import BreezeInput from "@/Components/Input.vue";
import BreezeInput2 from "@/Components/Input.vue";
import BreezeInputError from "@/Components/InputError.vue";
import BreezeLabel from "@/Components/Label.vue";
import { Head, Link, useForm } from "@inertiajs/inertia-vue3";
import { useDark, useToggle } from "@vueuse/core";
import { SunIcon, MoonIcon } from "@heroicons/vue/24/outline";

const form = useForm({
	staff_number: "",
	surname: "",
	first_name: "",
	email: "",
	phone: "",
});

const submit = () => {
	form.post(route("register"), {
		// onFinish: () => form.reset("password", "password_confirmation"),
	});
};
const dark = useDark();

const toggle = useToggle(dark);
</script>

<template>
	<BreezeGuestLayout>
		<Head title="Register" />

		<form @submit.prevent="submit" class="space-y-4">
			<div class="w-full md:w-1/2 lg:w-2/3">
				<BreezeLabel for="staff_number" value="Staff Number" />
				<BreezeInput
					id="staff_number"
					type="text"
					class="mt-1 block w-full"
					v-model="form.staff_number"
					required
					autofocus
					autocomplete="staff_number"
				/>
				<BreezeInputError class="mt-2" :message="form.errors.staff_number" />
			</div>
			<div class="md:w-full">
				<BreezeLabel for="first_name" value="First Name" />
				<BreezeInput
					id="first_name"
					type="text"
					class="mt-1 block w-full"
					v-model="form.first_name"
					required
					autofocus
					autocomplete="first_name"
				/>
				<BreezeInputError class="mt-2" :message="form.errors.first_name" />
			</div>
			<div class="md:full">
				<BreezeLabel for="surname" value="Surname" />
				<BreezeInput
					id="surname"
					type="text"
					class="mt-1 block w-full"
					v-model="form.surname"
					required
					autocomplete="surname"
				/>
				<BreezeInputError class="mt-2" :message="form.errors.surname" />
			</div>
			<!-- <div class="w-1/2">
				<BreezeLabel for="phone" value="Phone Number" />
				<BreezeInput
					id="phone"
					type="text"
					class="mt-1 block w-full"
					v-model="form.phone"
					required
					autocomplete="phone_number"
				/>
				<BreezeInputError class="mt-2" :message="form.errors.phone" />
			</div> -->
			<div class="w-4/5">
				<BreezeLabel for="email" value="Official Email" />
				<BreezeInput2
					id="email"
					type="text"
					postfix="@audit.gov.gh"
					align-text="right"
					class="mt-1 block w-full"
					v-model="form.email"
					required
					autocomplete="username"
				/>
				<BreezeInputError class="mt-2" :message="form.errors.email" />
				<!-- </div> -->
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
						:href="route('login')"
						class="underline text-sm text-gray-600 dark:text-gray-50 hover:text-gray-900"
					>
						Already registered?
					</Link>

					<BreezeButton
						class="ml-4"
						:class="{ 'opacity-25': form.processing }"
						:disabled="form.processing"
					>
						Register
					</BreezeButton>
				</div>
			</div>
		</form>
	</BreezeGuestLayout>
</template>
