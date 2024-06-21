<script setup>
import MainLayout from "@/Layouts/HrAuthenticated.vue";
import { Head, Link } from "@inertiajs/inertia-vue3";
import { Inertia } from "@inertiajs/inertia";
import { MagnifyingGlassIcon } from "@heroicons/vue/24/outline";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import BreezeInput from "@/Components/Input.vue";
import { ref } from "vue";
import { debouncedWatch } from "@vueuse/core";

let props = defineProps({
	institution: Object,
	jobs: Object,
	filters: Object,
});

//
let BreadcrumbLinks = [
	{ name: "Institutions", url: route("institution.index") },
	{ name: props.institution.name },
];

let search = ref(props.filters.search);

debouncedWatch(
	search,
	() => {
		Inertia.get(
			route("institution.jobs", {
				institution: props.institution.id,
			}),
			{ search: search.value },
			{ preserveState: true, replace: true, preserveScroll: true },
		);
	},
	{ debounce: 300 },
);
</script>

<template>
	<Head title="Dashboard" />

	<MainLayout>
		<template #header>
			<BreadCrumpVue :links="BreadcrumbLinks" />
			<h2 class="font-semibold text-xl text-gray-800 leading-tight pt-2">
				{{ institution.name }}
			</h2>
		</template>

		<div class="py-2">
			<div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
				<div class="bg-white overflow-hidden shadow-sm">
					<div class="p-4 md:flex justify-around">
						<div class="flex flex-col md:flex-row items-center">
							<h1 class="text-2xl font-bold tracking-wider text-gray-700">
								{{ institution.name }}
							</h1>
						</div>
					</div>
				</div>
				<div
					class="grid grid-cols-1 gap-6 my-6 md:grid-cols-2 lg:grid-cols-4 bg-w"
				>
					<!-- <InfoCard title="jobs" :value="jobs.staff" /> -->
				</div>

				<div
					v-if="jobs"
					class="shadow-lg rounded-2xl bg-white dark:bg-gray-700 mt-4 w-full lg:w-2/5"
				>
					<p
						class="font-bold text-xl px-8 pt-8 text-gray-700 dark:text-white tracking-wide"
					>
						Jobs
						<span class="text-lg text-gray-500 dark:text-white ml-2">
							({{ jobs.length }})
						</span>
					</p>

					<div class="mt-1 relative mx-8">
						<div
							class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
						>
							<span class="text-gray-500 sm:text-sm">
								<MagnifyingGlassIcon class="w-4 h-4" />
							</span>
						</div>
						<BreezeInput
							v-model="search"
							type="search"
							class="w-full pl-8 bg-slate-100 border-0"
							required
							autofocus
							placeholder="Search jobs..."
						/>
					</div>

					<ul class="px-8 pb-6 max-h-96 overflow-y-auto">
						<li
							v-for="(job, index) in jobs"
							:key="index"
							class="flex items-center text-gray-600 dark:text-gray-200 justify-between py-4 px-4 rounded-xl hover:bg-slate-200"
						>
							<div class="flex items-center justify-start text-lg">
								<span class="mr-4"> {{ index + 1 }} </span>
								<div class="flex flex-col">
									<Link
										:href="
											route('unit.show', {
												unit: job.id,
											})
										"
										class="font-semibold"
									>
										{{ job.name }}
									</Link>
									<div class="flex justify-start space-x-4">
										<span class="text-sm">
											Staff:
											{{ job.staff }}
										</span>
									</div>
								</div>
							</div>
						</li>
					</ul>
				</div>
			</div>
		</div>
	</MainLayout>
</template>
