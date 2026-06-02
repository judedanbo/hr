<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import { useToggle } from "@vueuse/core";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import Modal from "@/Components/NewModal.vue";
import LeaveStatusBadge from "@/Components/LeaveStatusBadge.vue";

const props = defineProps({
	request: { type: Object, required: true },
});

const page = usePage();
const permissions = computed(() => page.props?.auth.permissions);
const canResume = computed(() =>
	permissions.value?.includes("resume leave request"),
);
const canAmend = computed(() =>
	permissions.value?.includes("amend leave request"),
);

const openResume = ref(false);
const toggleResume = useToggle(openResume);
const openAmend = ref(false);
const toggleAmend = useToggle(openAmend);

const returnDate = ref("");
const amendForm = ref({
	start_date: props.request.start_date,
	end_date: props.request.end_date,
	reason: "",
});

const cancel = () => {
	if (!window.confirm("Cancel this leave request?")) return;
	router.post(
		route("leave-request.cancel", { leaveRequest: props.request.id }),
	);
};
const submitResume = () => {
	router.post(
		route("leave-request.resume", { leaveRequest: props.request.id }),
		{ actual_return_date: returnDate.value },
		{ preserveScroll: true, onSuccess: () => toggleResume() },
	);
};
const submitAmend = () => {
	router.post(
		route("leave-request.amend", { leaveRequest: props.request.id }),
		amendForm.value,
		{ preserveScroll: true, onSuccess: () => toggleAmend() },
	);
};

const links = [
	{ name: "My Leave", url: "/leave-request" },
	{ name: "Request", url: "" },
];
</script>

<template>
	<MainLayout>
		<Head title="Leave Request" />
		<main class="max-w-3xl mx-auto sm:px-6 lg:px-8">
			<BreadCrumpVue :links="links" />
			<div class="mt-6 rounded-md bg-white dark:bg-gray-800 p-6 shadow-sm">
				<div class="flex items-center justify-between">
					<h1 class="text-xl font-semibold text-gray-800 dark:text-gray-100">
						{{ request.leave_type }} — {{ request.requested_days }} day(s)
					</h1>
					<LeaveStatusBadge :status="request.status" />
				</div>
				<dl
					class="mt-4 grid grid-cols-2 gap-3 text-sm text-gray-700 dark:text-gray-200"
				>
					<div>
						<dt class="text-gray-400">Start</dt>
						<dd>{{ request.start_date }}</dd>
					</div>
					<div>
						<dt class="text-gray-400">End</dt>
						<dd>{{ request.end_date }}</dd>
					</div>
					<div>
						<dt class="text-gray-400">Address</dt>
						<dd>{{ request.address_during_leave }}</dd>
					</div>
					<div>
						<dt class="text-gray-400">Contact</dt>
						<dd>{{ request.contact_during_leave }}</dd>
					</div>
					<div>
						<dt class="text-gray-400">Relieving officer</dt>
						<dd>{{ request.relieving_officer || "—" }}</dd>
					</div>
					<div>
						<dt class="text-gray-400">Approver</dt>
						<dd>{{ request.approver || "Pending assignment" }}</dd>
					</div>
					<div v-if="request.approved_days">
						<dt class="text-gray-400">Approved days</dt>
						<dd>{{ request.approved_days }}</dd>
					</div>
					<div
						v-if="
							request.actual_days !== null && request.actual_days !== undefined
						"
					>
						<dt class="text-gray-400">
							Days used (returned {{ request.actual_return_date }})
						</dt>
						<dd>{{ request.actual_days }}</dd>
					</div>
					<div v-if="request.decline_reason" class="col-span-2">
						<dt class="text-gray-400">Decline reason</dt>
						<dd>{{ request.decline_reason }}</dd>
					</div>
					<div class="col-span-2">
						<dt class="text-gray-400">Reason</dt>
						<dd>{{ request.reason || "—" }}</dd>
					</div>
				</dl>

				<div class="mt-5">
					<h2 class="font-semibold text-gray-700 dark:text-gray-100">
						Evidence
					</h2>
					<ul class="mt-1 text-sm">
						<li v-for="doc in request.documents" :key="doc.id">
							<a
								:href="
									route('leave-request.documents.download', {
										leaveRequest: request.id,
										document: doc.id,
									})
								"
								class="text-green-700 hover:underline"
							>
								{{ doc.title || "Document" }} ({{ doc.file_type }})
							</a>
						</li>
						<li v-if="!request.documents.length" class="text-gray-400">None</li>
					</ul>
				</div>

				<div class="mt-5">
					<h2 class="font-semibold text-gray-700 dark:text-gray-100">
						History
					</h2>
					<ul class="mt-1 text-sm text-gray-600 dark:text-gray-300 space-y-1">
						<li v-for="(h, i) in request.history" :key="i">
							{{ h.at }} — {{ h.from || "—" }} → <strong>{{ h.to }}</strong>
							<span v-if="h.reason">({{ h.reason }})</span>
							<span v-if="h.by"> by {{ h.by }}</span>
						</li>
					</ul>
				</div>

				<div v-if="request.can_edit" class="mt-6 flex gap-x-3">
					<Link
						:href="route('leave-request.edit', { leaveRequest: request.id })"
						class="rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white hover:bg-green-500"
					>
						Edit
					</Link>
					<button
						type="button"
						class="rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white hover:bg-red-500"
						@click="cancel()"
					>
						Cancel request
					</button>
				</div>

				<div
					v-else-if="request.is_approved"
					class="mt-6 flex flex-wrap gap-x-3 gap-y-2"
				>
					<button
						v-if="canResume"
						type="button"
						class="rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-500"
						@click="toggleResume()"
					>
						Record return
					</button>
					<button
						v-if="canAmend"
						type="button"
						class="rounded-md bg-amber-600 px-3 py-2 text-sm font-semibold text-white hover:bg-amber-500"
						@click="toggleAmend()"
					>
						Amend
					</button>
					<button
						type="button"
						class="rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white hover:bg-red-500"
						@click="cancel()"
					>
						Cancel leave
					</button>
				</div>
			</div>
		</main>

		<Modal :show="openResume" @close="toggleResume()">
			<main class="px-8 py-8 bg-gray-100 dark:bg-gray-700">
				<h2 class="text-xl pb-2 dark:text-gray-100">Record return</h2>
				<p class="text-sm text-gray-600 dark:text-gray-200 pb-3">
					Unused days will be credited back to the balance.
				</p>
				<label class="block text-sm text-gray-700 dark:text-gray-200"
					>Actual return date</label
				>
				<input
					v-model="returnDate"
					type="date"
					class="mt-1 w-full rounded-md border-gray-300 dark:bg-gray-600 dark:text-gray-100"
				/>
				<div class="mt-4 flex justify-end gap-x-3">
					<button
						type="button"
						class="px-3 py-2 text-sm"
						@click="toggleResume()"
					>
						Cancel
					</button>
					<button
						type="button"
						class="rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-500"
						@click="submitResume()"
					>
						Save
					</button>
				</div>
			</main>
		</Modal>

		<Modal :show="openAmend" @close="toggleAmend()">
			<main class="px-8 py-8 bg-gray-100 dark:bg-gray-700">
				<h2 class="text-xl pb-2 dark:text-gray-100">Amend leave</h2>
				<p class="text-sm text-gray-600 dark:text-gray-200 pb-3">
					This cancels the current approval and submits a new request for
					approval.
				</p>
				<label class="block text-sm text-gray-700 dark:text-gray-200"
					>Start date</label
				>
				<input
					v-model="amendForm.start_date"
					type="date"
					class="mt-1 w-full rounded-md border-gray-300 dark:bg-gray-600 dark:text-gray-100"
				/>
				<label class="mt-3 block text-sm text-gray-700 dark:text-gray-200"
					>End date</label
				>
				<input
					v-model="amendForm.end_date"
					type="date"
					class="mt-1 w-full rounded-md border-gray-300 dark:bg-gray-600 dark:text-gray-100"
				/>
				<label class="mt-3 block text-sm text-gray-700 dark:text-gray-200"
					>Reason (optional)</label
				>
				<textarea
					v-model="amendForm.reason"
					rows="2"
					class="mt-1 w-full rounded-md border-gray-300 dark:bg-gray-600 dark:text-gray-100"
				/>
				<div class="mt-4 flex justify-end gap-x-3">
					<button
						type="button"
						class="px-3 py-2 text-sm"
						@click="toggleAmend()"
					>
						Cancel
					</button>
					<button
						type="button"
						class="rounded-md bg-amber-600 px-3 py-2 text-sm font-semibold text-white hover:bg-amber-500"
						@click="submitAmend()"
					>
						Submit amendment
					</button>
				</div>
			</main>
		</Modal>
	</MainLayout>
</template>
