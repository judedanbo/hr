<script setup>
import BreezeButton from "@/Components/Button.vue";
import BreezeCheckbox from "@/Components/Checkbox.vue";
import BreezeGuestLayout from "@/Layouts/Guest.vue";
import BreezeInput from "@/Components/Input.vue";
import BreezeInputError from "@/Components/InputError.vue";
import BreezeLabel from "@/Components/Label.vue";
import { Head, Link, useForm } from "@inertiajs/inertia-vue3";
import { useDark, useToggle } from "@vueuse/core";
import { SunIcon, MoonIcon } from "@heroicons/vue/24/outline";
defineProps({
  canResetPassword: Boolean,
  status: String,
});

const dark = useDark();

const toggle = useToggle(dark);

const form = useForm({
  email: "",
  password: "",
  remember: false,
});

const submit = () => {
  form.post(route("login"), {
    onFinish: () => form.reset("password"),
  });
};
</script>

<template>
  <BreezeGuestLayout>
    <Head title="Log in" />

    <div v-if="status" class="mb-4 font-medium text-sm text-green-600">
      {{ status }}
    </div>

    <form @submit.prevent="submit" id="loginForm" name="loginForm">
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
          autocomplete="current-password"
        />
        <BreezeInputError class="mt-2" :message="form.errors.password" />
      </div>

      <div class="block mt-4">
        <label class="flex items-center">
          <BreezeCheckbox name="remember" v-model:checked="form.remember" />
          <span class="ml-2 text-sm text-gray-600 dark:text-gray-50"
            >Remember me</span
          >
        </label>
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
            v-if="canResetPassword"
            :href="route('password.request')"
            class="underline text-sm text-gray-600 hover:text-green-900 dark:text-gray-400 dark:hover:text-gray-50"
          >
            Forgot your password?
          </Link>

          <BreezeButton
            class="ml-4"
            :class="{ 'opacity-25': form.processing }"
            :disabled="form.processing"
          >
            Log in
          </BreezeButton>
        </div>
      </div>
    </form>
  </BreezeGuestLayout>
</template>
