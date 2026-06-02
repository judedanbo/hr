<script setup>
import MainLayout from "@/Layouts/NewAuthenticated.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import BreadCrumpVue from "@/Components/BreadCrump.vue";
import LeaveStatusBadge from "@/Components/LeaveStatusBadge.vue";

const props = defineProps({
	request: { type: Object, required: true },
});

const cancel = () => {
	if (!window.confirm("Cancel this leave request?")) return;
	router.post(
		route("leave-request.cancel", { leaveRequest: props.request.id }),
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
			</div>
		</main>
	</MainLayout>
</template>
