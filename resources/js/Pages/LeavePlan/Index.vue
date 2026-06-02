<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, usePage } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import Modal from "@/Components/NewModal.vue";
import Delete from "@/Components/Delete.vue";
import { useToggle } from "@vueuse/core";
import PlanItemList from "./partials/PlanItemList.vue";
import AddPlanItemForm from "./partials/AddPlanItemForm.vue";
import EditPlanItemForm from "./partials/EditPlanItemForm.vue";

const props = defineProps({
	windowOpen: { type: Boolean, default: false },
	window: { type: Object, default: null },
	plan: { type: Object, default: null },
	items: { type: Array, default: () => [] },
	ledger: { type: Array, default: () => [] },
	leaveTypes: { type: Array, default: () => [] },
});

const page = usePage();
const permissions = computed(() => page.props?.auth.permissions);
const canEdit = computed(
	() => props.windowOpen && permissions.value?.includes("submit leave plan"),
);

const openAdd = ref(false);
const toggleAdd = useToggle(openAdd);
const openEdit = ref(false);
const toggleEdit = useToggle(openEdit);
const openDelete = ref(false);
const toggleDelete = useToggle(openDelete);
const selected = ref(null);

const editItem = (item) => {
	selected.value = item;
	toggleEdit();
};
const deleteItem = (item) => {
	selected.value = item;
	toggleDelete();
};
const deleteConfirmed = () => {
	router.delete(
		route("leave-plan.items.destroy", { item: selected.value.id }),
		{
			preserveScroll: true,
			onSuccess: () => toggleDelete(),
		},
	);
};

const submitPlan = () => {
	router.post(route("leave-plan.submit"), {}, { preserveScroll: true });
};

const links = [{ name: "My Leave Plan", url: "" }];
</script>

<template>
	<MainLayout>
		<Head title="My Leave Plan" />
		<main class="max-w-7xl mx-auto sm:px-6 lg:px-8">
			<BreadCrumpVue :links="links" />

			<div
				v-if="!windowOpen && !plan"
				class="mt-6 rounded-md bg-gray-50 dark:bg-gray-700 p-8 text-center text-gray-600 dark:text-gray-200"
			>
				Leave planning is not currently open. You will be notified when the
				window opens.
			</div>

			<template v-else>
				<div
					v-if="windowOpen"
					class="mt-6 rounded-md border border-green-200 bg-green-50 dark:bg-gray-700 p-4 text-sm text-green-900 dark:text-green-100"
				>
					<p class="font-semibold">
						Planning open for {{ plan?.year }} — closes {{ window?.closes_at }}
					</p>
					<p v-if="window?.instructions" class="mt-1">
						{{ window.instructions }}
					</p>
					<p v-if="plan?.status === 'Submitted'" class="mt-1">
						Submitted {{ plan.submitted_at }} — you can still make changes while
						the window is open.
					</p>
				</div>

				<section class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
					<div
						v-for="row in ledger"
						:key="row.leave_type_id"
						class="rounded-md border border-gray-200 dark:border-gray-600 p-4 bg-white dark:bg-gray-800"
					>
						<p class="font-semibold text-gray-800 dark:text-gray-100">
							{{ row.leave_type }}
						</p>
						<div
							class="mt-2 flex justify-between text-sm text-gray-600 dark:text-gray-300"
						>
							<span>Assigned: {{ row.assigned }}</span>
							<span>Planned: {{ row.planned }}</span>
							<span>Unplanned: {{ row.unplanned }}</span>
						</div>
						<div
							class="mt-2 h-2 w-full rounded-full bg-gray-200 dark:bg-gray-600"
						>
							<div
								class="h-2 rounded-full bg-green-600"
								:style="{
									width:
										Math.min(
											100,
											row.assigned ? (row.planned / row.assigned) * 100 : 0,
										) + '%',
								}"
							/>
						</div>
					</div>
				</section>

				<div class="mt-6 flex justify-end gap-x-3">
					<button
						v-if="canEdit"
						type="button"
						class="rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white hover:bg-green-500"
						@click="toggleAdd()"
					>
						Add planned leave
					</button>
					<button
						v-if="canEdit"
						type="button"
						class="rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-500"
						@click="submitPlan()"
					>
						{{
							plan?.status === "Submitted" ? "Re-submit plan" : "Submit plan"
						}}
					</button>
				</div>

				<PlanItemList
					:items="items"
					:can-edit="canEdit"
					@edit-item="(item) => editItem(item)"
					@delete-item="(item) => deleteItem(item)"
				/>
			</template>
		</main>

		<Modal :show="openAdd" @close="toggleAdd()">
			<AddPlanItemForm
				:leave-types="leaveTypes"
				@form-submitted="toggleAdd()"
			/>
		</Modal>
		<Modal :show="openEdit" @close="toggleEdit()">
			<EditPlanItemForm
				:item="selected"
				:leave-types="leaveTypes"
				@form-submitted="toggleEdit()"
			/>
		</Modal>
		<Delete
			:show="openDelete"
			model-name="plan item"
			@close="toggleDelete()"
			@delete-confirmed="deleteConfirmed()"
		/>
	</MainLayout>
</template>
