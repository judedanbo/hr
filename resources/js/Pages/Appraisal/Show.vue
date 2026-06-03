<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, router } from "@inertiajs/vue3";
import { ref } from "vue";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import Modal from "@/Components/NewModal.vue";
import { useToggle } from "@vueuse/core";
import ObjectiveForm from "./partials/ObjectiveForm.vue";

const props = defineProps({
	appraisal: { type: Object, required: true },
});

const openDialog = ref(false);
const toggle = useToggle(openDialog);
const openEditDialog = ref(false);
const toggleEdit = useToggle(openEditDialog);
const selected = ref(null);

const editObjective = (objective) => {
	selected.value = objective;
	toggleEdit();
};
const deleteObjective = (objective) => {
	router.delete(route("appraisal.objective.delete", { appraisal: props.appraisal.id, objective: objective.id }), { preserveScroll: true });
};
const submitObjectives = () => {
	router.post(route("appraisal.objectives.submit", { appraisal: props.appraisal.id }), {}, { preserveScroll: true });
};
const agreeObjectives = () => {
	router.post(route("appraisal.objectives.agree", { appraisal: props.appraisal.id }), {}, { preserveScroll: true });
};

const canEditObjectives = () =>
	props.appraisal.status === "draft_objectives" &&
	(props.appraisal.can.view_all || props.appraisal.can.is_owner);

const links = [
	{ name: "Appraisals", url: route("appraisal.index") },
	{ name: props.appraisal.staff_name ?? "Appraisal", url: "" },
];
</script>

<template>
	<MainLayout>
		<Head :title="`Appraisal — ${appraisal.staff_name}`" />
		<main class="max-w-7xl mx-auto sm:px-6 lg:px-8">
			<BreadCrumpVue :links="links" />

			<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mt-4">
				<div class="flex items-center justify-between">
					<div>
						<h1 class="text-2xl font-semibold dark:text-gray-100">{{ appraisal.staff_name }}</h1>
						<p class="text-gray-500 dark:text-gray-300">{{ appraisal.cycle }} · {{ appraisal.unit ?? "No unit" }}</p>
					</div>
					<span :class="appraisal.status_color" class="font-semibold">{{ appraisal.status_label }}</span>
				</div>
				<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4 text-sm dark:text-gray-200">
					<div><span class="text-gray-500 dark:text-gray-400">Appraiser:</span> {{ appraisal.appraiser_name }}</div>
					<div><span class="text-gray-500 dark:text-gray-400">Reviewer:</span> {{ appraisal.reviewer_name }}</div>
					<div><span class="text-gray-500 dark:text-gray-400">Overall:</span> {{ appraisal.overall_score ?? "—" }} <span v-if="appraisal.overall_band">({{ appraisal.overall_band }})</span></div>
					<div><span class="text-gray-500 dark:text-gray-400">Weights:</span> {{ appraisal.objectives_weight }}% / {{ appraisal.competencies_weight }}%</div>
				</div>
			</div>

			<!-- Objectives -->
			<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mt-4">
				<div class="flex items-center justify-between mb-3">
					<h2 class="text-lg font-semibold dark:text-gray-100">
						Objectives
						<span class="text-sm font-normal" :class="appraisal.objectives_total_weight === 100 ? 'text-green-600' : 'text-amber-500'">
							(total weight {{ appraisal.objectives_total_weight }}%)
						</span>
					</h2>
					<button v-if="canEditObjectives()" class="rounded-md bg-green-600 px-3 py-1.5 text-sm text-white hover:bg-green-500" @click="toggle()">
						Add objective
					</button>
				</div>
				<table class="min-w-full text-sm dark:text-gray-200">
					<thead>
						<tr class="text-left text-gray-500 dark:text-gray-400">
							<th class="py-2">Objective</th>
							<th class="py-2">Weight</th>
							<th class="py-2">Self</th>
							<th class="py-2">Supervisor</th>
							<th v-if="canEditObjectives()" class="py-2 text-right">Action</th>
						</tr>
					</thead>
					<tbody>
						<tr v-for="objective in appraisal.objectives" :key="objective.id" class="border-t border-gray-100 dark:border-gray-700">
							<td class="py-2">
								<div class="font-medium">{{ objective.title }}</div>
								<div class="text-gray-500 dark:text-gray-400">{{ objective.measure }}</div>
							</td>
							<td class="py-2">{{ objective.weight }}%</td>
							<td class="py-2">{{ objective.self_score ?? "—" }}</td>
							<td class="py-2">{{ objective.supervisor_score ?? "—" }}</td>
							<td v-if="canEditObjectives()" class="py-2 text-right">
								<button class="text-blue-600 hover:underline mr-3" @click="editObjective(objective)">Edit</button>
								<button class="text-red-600 hover:underline" @click="deleteObjective(objective)">Delete</button>
							</td>
						</tr>
						<tr v-if="!appraisal.objectives.length">
							<td colspan="5" class="py-4 text-center text-gray-500 dark:text-gray-400">No objectives yet.</td>
						</tr>
					</tbody>
				</table>

				<div class="mt-4 flex gap-3">
					<button
						v-if="appraisal.status === 'draft_objectives' && appraisal.can.is_owner"
						class="rounded-md bg-blue-600 px-3 py-1.5 text-sm text-white hover:bg-blue-500"
						@click="submitObjectives()"
					>
						Submit to appraiser
					</button>
					<button
						v-if="appraisal.status === 'draft_objectives' && (appraisal.can.review || appraisal.can.view_all)"
						class="rounded-md bg-green-600 px-3 py-1.5 text-sm text-white hover:bg-green-500"
						@click="agreeObjectives()"
					>
						Agree objectives
					</button>
				</div>
			</div>

			<!-- History -->
			<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mt-4">
				<h2 class="text-lg font-semibold mb-3 dark:text-gray-100">History</h2>
				<ol class="space-y-2 text-sm dark:text-gray-200">
					<li v-for="(entry, i) in appraisal.history" :key="i" class="flex justify-between border-b border-gray-100 dark:border-gray-700 pb-1">
						<span>{{ entry.status_label }} <span v-if="entry.comment" class="text-gray-500">— {{ entry.comment }}</span></span>
						<span class="text-gray-500 dark:text-gray-400">{{ entry.actor }} · {{ entry.at }}</span>
					</li>
					<li v-if="!appraisal.history.length" class="text-gray-500 dark:text-gray-400">No history yet.</li>
				</ol>
			</div>
		</main>

		<Modal :show="openDialog" @close="toggle()">
			<ObjectiveForm :appraisal-id="appraisal.id" @form-submitted="toggle()" />
		</Modal>
		<Modal :show="openEditDialog" @close="toggleEdit()">
			<ObjectiveForm :appraisal-id="appraisal.id" :objective="selected" @form-submitted="toggleEdit()" />
		</Modal>
	</MainLayout>
</template>
