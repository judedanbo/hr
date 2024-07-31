<script setup>
import { Link } from "@inertiajs/inertia-vue3";
import {
	CalendarDaysIcon,
	UserPlusIcon,
	FlagIcon,
	IdentificationIcon,
} from "@heroicons/vue/20/solid";
const emit = defineEmits(["openEditPerson"]);
defineProps({
	person: {
		type: Object,
		default: () => ({}),
	},
});
</script>
<template>
	<!-- Personal Details summary -->
	<main class="w-full">
		<h2 class="sr-only">Personal Details Summary</h2>
		<div
			class="md:rounded-lg bg-gray-50 dark:bg-gray-500 shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-500/30"
		>
			<dl class="flex flex-wrap">
				<div class="flex-auto pl-6 pt-6">
					<dt
						class="text-sm font-semibold leading-6 text-gray-900 dark:text-gray-100"
					>
						Personal Details
					</dt>
					<dd
						class="mt-1 text-base font-semibold leading-6 text-gray-900 dark:text-gray-100"
					>
						{{ person.name }}
					</dd>
				</div>
				<div class="flex-none self-end px-6 pt-4">
					<Link
						v-if="$page.props.permissions.includes('view staff')"
						:href="route('person.show', { person: person.id })"
						class="rounded-md bg-green-50 dark:bg-gray-400 px-2 py-1 text-xs font-medium text-green-600 dark:text-gray-50 ring-1 ring-inset ring-green-600/20 dark:ring-gray-500"
					>
						View
					</Link>
				</div>

				<div
					class="mt-4 flex w-full flex-none gap-x-4 px-6 pt-6 border-t border-gray-900/5 dark:border-gray-200/30"
				>
					<dt class="flex-none">
						<span class="sr-only">Date of birth</span>
						<CalendarDaysIcon
							class="h-6 w-5 text-gray-400 dark:text-gray-50"
							aria-hidden="true"
						/>
					</dt>
					<dd class="text-sm leading-6 text-gray-500 dark:text-gray-50">
						<time :datetime="person.dob_value"> {{ person.dob }}</time>
						({{ person.dob_distance }})
					</dd>
				</div>
				<div class="mt-4 flex w-full flex-none gap-x-4 px-6">
					<dt class="flex-none">
						<span class="sr-only">Gender</span>
						<svg
							class="text-gray-400 h-6 w-5 dark:text-gray-50"
							xmlns="http://www.w3.org/2000/svg"
							width="32"
							height="32"
							viewBox="0 0 24 24"
						>
							<path
								fill="currentColor"
								d="M17.58 4H14V2h7v7h-2V5.41l-3.83 3.83A5 5 0 0 1 12 16.9V19h2v2h-2v2h-2v-2H8v-2h2v-2.1A5 5 0 0 1 6 12a5 5 0 0 1 5-5c1 0 1.96.3 2.75.83L17.58 4M11 9a3 3 0 0 0-3 3a3 3 0 0 0 3 3a3 3 0 0 0 3-3a3 3 0 0 0-3-3Z"
							/>
						</svg>
					</dt>
					<dd class="text-sm leading-6 text-gray-500 dark:text-gray-50">
						{{ person.gender ?? "Gender Not Specified" }}
					</dd>
				</div>
				<div class="mt-4 flex w-full flex-none gap-x-4 px-6">
					<dt class="flex-none">
						<span class="sr-only">Marital Status</span>
						<UserPlusIcon
							class="h-6 w-5 text-gray-400 dark:text-gray-50"
							aria-hidden="true"
						/>
					</dt>
					<dd class="text-sm leading-6 text-gray-500 dark:text-gray-50">
						{{ person.marital_status ?? "Marital Status Not Specified" }}
					</dd>
				</div>
				<div class="mt-4 flex w-full flex-none gap-x-4 px-6">
					<dt class="flex-none">
						<span class="sr-only">Nationality</span>
						<FlagIcon
							class="h-6 w-5 text-gray-400 dark:text-gray-50"
							aria-hidden="true"
						/>
					</dt>
					<dd class="text-sm leading-6 text-gray-500 dark:text-gray-50">
						{{ person.nationality ?? "Nationality Not Specified" }}
					</dd>
				</div>
				<div class="mt-4 flex w-full flex-none gap-x-4 px-6">
					<dt class="flex-none">
						<span class="sr-only">Religion</span>
						<svg
							class="text-gray-400 dark:text-gray-50 h-6 w-5"
							xmlns="http://www.w3.org/2000/svg"
							width="32"
							height="32"
							viewBox="0 0 448 512"
						>
							<path
								fill="currentColor"
								d="M352 64a64 64 0 1 0-128 0a64 64 0 1 0 128 0zM232.7 264l22.9 31.5c6.5 8.9 16.3 14.7 27.2 16.1s21.9-1.7 30.4-8.7l88-72c17.1-14 19.6-39.2 5.6-56.3s-39.2-19.6-56.3-5.6l-55.2 45.2l-26.2-36C253.6 156.7 228.6 144 202 144c-30.9 0-59.2 17.1-73.6 44.4l-48.6 92.5c-20.2 38.5-9.4 85.9 25.6 111.8l53.2 39.3H72c-22.1 0-40 17.9-40 40s17.9 40 40 40h208c17.3 0 32.6-11.1 38-27.5s-.3-34.4-14.2-44.7L187.7 354l45-90z"
							/>
						</svg>
					</dt>

					<dd
						v-if="person.religion || person.religion == ''"
						class="text-sm leading-6 text-gray-500 dark:text-gray-50"
					>
						{{ person.religion }}
					</dd>
				</div>

				<div
					v-for="ids in person.identities"
					:key="ids.id_number"
					class="mt-4 flex w-full flex-none gap-x-4 px-6"
				>
					<template v-if="ids.id_type == 'G'">
						<dt class="flex-none">
							<span class="sr-only">{{ ids.id_type }}</span>
							<IdentificationIcon
								class="h-6 w-5 text-gray-400 dark:text-gray-50"
								aria-hidden="true"
							/>
						</dt>
						<dd class="text-sm leading-6 text-gray-500 dark:text-gray-50">
							{{ ids.id_number }}
						</dd>
					</template>
				</div>
			</dl>
			<div
				class="mt-6 border-t border-gray-900/5 dark:border-gray-200/30 px-6 py-6"
			>
				<p
					@click="emit('openEditPerson')"
					class="text-sm font-semibold leading-6 text-gray-900 dark:text-gray-100 cursor-pointer"
				>
					Edit personal details <span aria-hidden="true">&rarr;</span>
				</p>
			</div>
		</div>
	</main>
</template>
