<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, router } from "@inertiajs/vue3";
import { ref, reactive, computed } from "vue";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import Modal from "@/Components/NewModal.vue";
import { useToggle } from "@vueuse/core";
import ObjectiveForm from "./partials/ObjectiveForm.vue";

const props = defineProps({
	appraisal: { type: Object, required: true },
});

const a = computed(() => props.appraisal);
const status = computed(() => props.appraisal.status);
const can = computed(() => props.appraisal.can);

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
	router.delete(route("appraisal.objective.delete", { appraisal: a.value.id, objective: objective.id }), { preserveScroll: true });
};

const post = (name) => router.post(route(name, { appraisal: a.value.id }), {}, { preserveScroll: true });

const canEditObjectives = computed(() => status.value === "draft_objectives" && (can.value.view_all || can.value.is_owner));
const selfMode = computed(() => status.value === "self_appraisal" && (can.value.is_owner || can.value.view_all));
const reviewMode = computed(() => status.value === "supervisor_review" && (can.value.review || can.value.view_all));
const scoringField = computed(() => (selfMode.value ? "self_score" : "supervisor_score"));

// Editable score model for self / supervisor stages.
const scores = reactive({
	objectives: a.value.objectives.map((o) => ({ id: o.id, score: o[scoringField.value] })),
	competencies: a.value.competency_ratings.map((c) => ({ id: c.id, score: c[scoringField.value] })),
});

const submitScores = (name) => {
	router.post(route(name, { appraisal: a.value.id }), { objectives: scores.objectives, competencies: scores.competencies }, { preserveScroll: true });
};

const links = [
	{ name: "Appraisals", url: route("appraisal.index") },
	{ name: a.value.staff_name ?? "Appraisal", url: "" },
];
</script>

<template>
	<MainLayout>
		<Head :title="`Appraisal — ${a.staff_name}`" />
		<main class="max-w-7xl mx-auto sm:px-6 lg:px-8">
			<BreadCrumpVue :links="links" />

			<!-- Header -->
			<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mt-4">
				<div class="flex items-center justify-between">
					<div>
						<h1 class="text-2xl font-semibold dark:text-gray-100">{{ a.staff_name }}</h1>
						<p class="text-gray-500 dark:text-gray-300">{{ a.cycle }} · {{ a.unit ?? "No unit" }}</p>
					</div>
					<div class="flex items-center gap-3">
						<span :class="a.status_color" class="font-semibold">{{ a.status_label }}</span>
						<a :href="route('appraisal.pdf', { appraisal: a.id })" class="rounded-md bg-gray-600 px-3 py-1.5 text-sm text-white hover:bg-gray-500">PDF</a>
					</div>
				</div>
				<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4 text-sm dark:text-gray-200">
					<div><span class="text-gray-500 dark:text-gray-400">Appraiser:</span> {{ a.appraiser_name }}</div>
					<div><span class="text-gray-500 dark:text-gray-400">Reviewer:</span> {{ a.reviewer_name }}</div>
					<div><span class="text-gray-500 dark:text-gray-400">Overall:</span> {{ a.overall_score ?? "—" }} <span v-if="a.overall_band">({{ a.overall_band }})</span></div>
					<div><span class="text-gray-500 dark:text-gray-400">Weights:</span> {{ a.objectives_weight }}% / {{ a.competencies_weight }}%</div>
				</div>
			</div>

			<!-- Workflow actions -->
			<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 mt-4 flex flex-wrap gap-3">
				<button v-if="status === 'draft_objectives' && can.is_owner" class="btn-blue" @click="post('appraisal.objectives.submit')">Submit objectives to appraiser</button>
				<button v-if="status === 'draft_objectives' && (can.review || can.view_all)" class="btn-green" @click="post('appraisal.objectives.agree')">Agree objectives</button>
				<button v-if="status === 'objectives_agreed' && (can.review || can.view_all)" class="btn-blue" @click="post('appraisal.midyear.start')">Start mid-year</button>
				<button v-if="status === 'midyear_in_progress' && (can.review || can.view_all)" class="btn-green" @click="post('appraisal.midyear.complete')">Complete mid-year</button>
				<button v-if="['objectives_agreed','midyear_completed'].includes(status) && (can.review || can.view_all)" class="btn-blue" @click="post('appraisal.self.open')">Open self-appraisal</button>
				<button v-if="status === 'reviewer_review' && (can.countersign || can.view_all)" class="btn-green" @click="post('appraisal.countersign')">Countersign</button>
				<button v-if="status === 'reviewer_review' && (can.countersign || can.view_all)" class="btn-red" @click="post('appraisal.return')">Return to supervisor</button>
				<button v-if="status === 'awaiting_acknowledgement' && can.is_owner" class="btn-green" @click="post('appraisal.acknowledge')">Acknowledge</button>
				<span v-if="status === 'completed'" class="text-green-600 font-semibold">Appraisal completed{{ a.acknowledged_at ? ` · acknowledged ${a.acknowledged_at}` : "" }}.</span>
			</div>

			<!-- Objectives -->
			<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mt-4">
				<div class="flex items-center justify-between mb-3">
					<h2 class="text-lg font-semibold dark:text-gray-100">
						Objectives
						<span class="text-sm font-normal" :class="a.objectives_total_weight === 100 ? 'text-green-600' : 'text-amber-500'">(total weight {{ a.objectives_total_weight }}%)</span>
					</h2>
					<button v-if="canEditObjectives" class="btn-green" @click="toggle()">Add objective</button>
				</div>
				<table class="min-w-full text-sm dark:text-gray-200">
					<thead>
						<tr class="text-left text-gray-500 dark:text-gray-400">
							<th class="py-2">Objective</th>
							<th class="py-2">Weight</th>
							<th class="py-2">Self</th>
							<th class="py-2">Supervisor</th>
							<th v-if="canEditObjectives" class="py-2 text-right">Action</th>
						</tr>
					</thead>
					<tbody>
						<tr v-for="(objective, i) in a.objectives" :key="objective.id" class="border-t border-gray-100 dark:border-gray-700">
							<td class="py-2">
								<div class="font-medium">{{ objective.title }}</div>
								<div class="text-gray-500 dark:text-gray-400">{{ objective.measure }}</div>
								<div v-if="objective.midyear_progress" class="text-xs text-gray-400">Mid-year: {{ objective.midyear_progress }}</div>
							</td>
							<td class="py-2">{{ objective.weight }}%</td>
							<td class="py-2">
								<input v-if="selfMode" v-model.number="scores.objectives[i].score" type="number" min="0" max="100" class="w-20 rounded border-gray-300 dark:bg-gray-700" />
								<span v-else>{{ objective.self_score ?? "—" }}</span>
							</td>
							<td class="py-2">
								<input v-if="reviewMode" v-model.number="scores.objectives[i].score" type="number" min="0" max="100" class="w-20 rounded border-gray-300 dark:bg-gray-700" />
								<span v-else>{{ objective.supervisor_score ?? "—" }}</span>
							</td>
							<td v-if="canEditObjectives" class="py-2 text-right">
								<button class="text-blue-600 hover:underline mr-3" @click="editObjective(objective)">Edit</button>
								<button class="text-red-600 hover:underline" @click="deleteObjective(objective)">Delete</button>
							</td>
						</tr>
						<tr v-if="!a.objectives.length"><td colspan="5" class="py-4 text-center text-gray-500 dark:text-gray-400">No objectives yet.</td></tr>
					</tbody>
				</table>
			</div>

			<!-- Competencies -->
			<div v-if="a.competency_ratings.length" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mt-4">
				<h2 class="text-lg font-semibold mb-3 dark:text-gray-100">Competencies</h2>
				<table class="min-w-full text-sm dark:text-gray-200">
					<thead>
						<tr class="text-left text-gray-500 dark:text-gray-400">
							<th class="py-2">Competency</th>
							<th class="py-2">Weight</th>
							<th class="py-2">Self</th>
							<th class="py-2">Supervisor</th>
						</tr>
					</thead>
					<tbody>
						<tr v-for="(rating, i) in a.competency_ratings" :key="rating.id" class="border-t border-gray-100 dark:border-gray-700">
							<td class="py-2">{{ rating.competency }}</td>
							<td class="py-2">{{ rating.weight }}%</td>
							<td class="py-2">
								<input v-if="selfMode" v-model.number="scores.competencies[i].score" type="number" min="0" max="100" class="w-20 rounded border-gray-300 dark:bg-gray-700" />
								<span v-else>{{ rating.self_score ?? "—" }}</span>
							</td>
							<td class="py-2">
								<input v-if="reviewMode" v-model.number="scores.competencies[i].score" type="number" min="0" max="100" class="w-20 rounded border-gray-300 dark:bg-gray-700" />
								<span v-else>{{ rating.supervisor_score ?? "—" }}</span>
							</td>
						</tr>
					</tbody>
				</table>
			</div>

			<!-- Score submission -->
			<div v-if="selfMode || reviewMode" class="mt-4">
				<button v-if="selfMode" class="btn-blue" @click="submitScores('appraisal.self.submit')">Submit self-appraisal</button>
				<button v-if="reviewMode" class="btn-green" @click="submitScores('appraisal.review.submit')">Submit supervisor review</button>
			</div>

			<!-- History -->
			<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mt-4">
				<h2 class="text-lg font-semibold mb-3 dark:text-gray-100">History</h2>
				<ol class="space-y-2 text-sm dark:text-gray-200">
					<li v-for="(entry, i) in a.history" :key="i" class="flex justify-between border-b border-gray-100 dark:border-gray-700 pb-1">
						<span>{{ entry.status_label }} <span v-if="entry.comment" class="text-gray-500">— {{ entry.comment }}</span></span>
						<span class="text-gray-500 dark:text-gray-400">{{ entry.actor }} · {{ entry.at }}</span>
					</li>
					<li v-if="!a.history.length" class="text-gray-500 dark:text-gray-400">No history yet.</li>
				</ol>
			</div>
		</main>

		<Modal :show="openDialog" @close="toggle()">
			<ObjectiveForm :appraisal-id="a.id" @form-submitted="toggle()" />
		</Modal>
		<Modal :show="openEditDialog" @close="toggleEdit()">
			<ObjectiveForm :appraisal-id="a.id" :objective="selected" @form-submitted="toggleEdit()" />
		</Modal>
	</MainLayout>
</template>

<style scoped>
.btn-green {
	@apply rounded-md bg-green-600 px-3 py-1.5 text-sm text-white hover:bg-green-500;
}
.btn-blue {
	@apply rounded-md bg-blue-600 px-3 py-1.5 text-sm text-white hover:bg-blue-500;
}
.btn-red {
	@apply rounded-md bg-red-600 px-3 py-1.5 text-sm text-white hover:bg-red-500;
}
</style>
