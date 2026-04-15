<script setup>
import { ref } from "vue";
import {
	Dialog,
	DialogPanel,
	TransitionChild,
	TransitionRoot,
	Menu,
	MenuButton,
	MenuItem,
	MenuItems,
} from "@headlessui/vue";
import {
	ArrowsPointingOutIcon,
	XMarkIcon,
	EllipsisVerticalIcon,
	CheckIcon,
	ArrowDownTrayIcon,
} from "@heroicons/vue/24/outline";
import { jsPDF } from "jspdf";

const props = defineProps({
	title: { type: String, default: "Chart" },
});

const open = ref(false);
const labelMode = ref("count");
const inlineRoot = ref(null);
const modalRoot = ref(null);
const exporting = ref(false);

const labelOptions = [
	{ value: "count", label: "Count" },
	{ value: "percent", label: "Percentage" },
	{ value: "both", label: "Count + %" },
	{ value: "none", label: "Hidden" },
];

function sanitizeFilename(name) {
	return name.toLowerCase().replace(/[^a-z0-9]+/g, "-").replace(/^-|-$/g, "");
}

function exportToPdf(source) {
	const canvas = source?.querySelector("canvas");
	if (!canvas) return;

	exporting.value = true;
	try {
		// Draw the chart onto a white-backed canvas first so the PDF background
		// isn't transparent black when the browser is in dark mode.
		const off = document.createElement("canvas");
		off.width = canvas.width;
		off.height = canvas.height;
		const ctx = off.getContext("2d");
		ctx.fillStyle = "#ffffff";
		ctx.fillRect(0, 0, off.width, off.height);
		ctx.drawImage(canvas, 0, 0);
		const dataUrl = off.toDataURL("image/png");

		const pdf = new jsPDF({
			orientation: canvas.width >= canvas.height ? "landscape" : "portrait",
			unit: "pt",
			format: "a4",
		});

		const pageWidth = pdf.internal.pageSize.getWidth();
		const pageHeight = pdf.internal.pageSize.getHeight();
		const margin = 36; // 0.5in
		const headerHeight = 48;
		const footerHeight = 24;

		pdf.setFont("helvetica", "bold");
		pdf.setFontSize(14);
		pdf.text(props.title, margin, margin + 16);

		pdf.setFont("helvetica", "normal");
		pdf.setFontSize(9);
		pdf.setTextColor(120);
		pdf.text(
			`Generated ${new Date().toLocaleString()}`,
			margin,
			margin + 32,
		);
		pdf.setTextColor(0);

		const availW = pageWidth - margin * 2;
		const availH = pageHeight - margin * 2 - headerHeight - footerHeight;
		const ratio = canvas.width / canvas.height;
		let imgW = availW;
		let imgH = imgW / ratio;
		if (imgH > availH) {
			imgH = availH;
			imgW = imgH * ratio;
		}
		const imgX = margin + (availW - imgW) / 2;
		const imgY = margin + headerHeight;
		pdf.addImage(dataUrl, "PNG", imgX, imgY, imgW, imgH);

		pdf.setFontSize(8);
		pdf.setTextColor(150);
		pdf.text(
			"HRMIS · Qualification Reports",
			margin,
			pageHeight - margin + 12,
		);

		pdf.save(`${sanitizeFilename(props.title)}.pdf`);
	} finally {
		exporting.value = false;
	}
}
</script>

<template>
	<div ref="inlineRoot" class="relative h-full">
		<div class="absolute top-2 right-2 z-10 flex gap-1">
			<button
				type="button"
				class="p-1.5 rounded-md bg-white/70 dark:bg-gray-900/70 hover:bg-white dark:hover:bg-gray-900 text-gray-600 dark:text-gray-300 shadow-sm disabled:opacity-50"
				title="Export as PDF"
				:disabled="exporting"
				@click="exportToPdf(inlineRoot)"
			>
				<ArrowDownTrayIcon class="h-4 w-4" />
			</button>
			<Menu as="div" class="relative">
				<MenuButton
					class="p-1.5 rounded-md bg-white/70 dark:bg-gray-900/70 hover:bg-white dark:hover:bg-gray-900 text-gray-600 dark:text-gray-300 shadow-sm"
					title="Label options"
				>
					<EllipsisVerticalIcon class="h-4 w-4" />
				</MenuButton>
				<transition
					enter-active-class="transition ease-out duration-100"
					enter-from-class="transform opacity-0 scale-95"
					enter-to-class="transform opacity-100 scale-100"
					leave-active-class="transition ease-in duration-75"
					leave-from-class="transform opacity-100 scale-100"
					leave-to-class="transform opacity-0 scale-95"
				>
					<MenuItems
						class="absolute right-0 mt-1 w-40 origin-top-right rounded-md bg-white dark:bg-gray-800 shadow-lg ring-1 ring-black/5 dark:ring-white/10 focus:outline-none z-20"
					>
						<div class="py-1 text-xs uppercase tracking-wide text-gray-400 px-3 pt-2">Labels</div>
						<MenuItem
							v-for="opt in labelOptions"
							:key="opt.value"
							v-slot="{ active }"
						>
							<button
								type="button"
								class="w-full flex items-center justify-between px-3 py-1.5 text-sm text-gray-700 dark:text-gray-200"
								:class="active ? 'bg-gray-100 dark:bg-gray-700' : ''"
								@click="labelMode = opt.value"
							>
								<span>{{ opt.label }}</span>
								<CheckIcon v-if="labelMode === opt.value" class="h-4 w-4 text-indigo-600" />
							</button>
						</MenuItem>
					</MenuItems>
				</transition>
			</Menu>
			<button
				type="button"
				class="p-1.5 rounded-md bg-white/70 dark:bg-gray-900/70 hover:bg-white dark:hover:bg-gray-900 text-gray-600 dark:text-gray-300 shadow-sm"
				title="Expand chart"
				@click="open = true"
			>
				<ArrowsPointingOutIcon class="h-4 w-4" />
			</button>
		</div>

		<slot :label-mode="labelMode" />

		<TransitionRoot as="template" :show="open">
			<Dialog as="div" class="relative z-50" @close="open = false">
				<TransitionChild
					as="template"
					enter="ease-out duration-200"
					enter-from="opacity-0"
					enter-to="opacity-100"
					leave="ease-in duration-150"
					leave-from="opacity-100"
					leave-to="opacity-0"
				>
					<div class="fixed inset-0 bg-gray-900/75 transition-opacity" />
				</TransitionChild>

				<div class="fixed inset-0 z-50 overflow-y-auto">
					<div class="flex min-h-full items-center justify-center p-4">
						<TransitionChild
							as="template"
							enter="ease-out duration-200"
							enter-from="opacity-0 scale-95"
							enter-to="opacity-100 scale-100"
							leave="ease-in duration-150"
							leave-from="opacity-100 scale-100"
							leave-to="opacity-0 scale-95"
						>
							<DialogPanel
								ref="modalRoot"
								class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl w-[95vw] h-[90vh] max-w-[1400px] flex flex-col p-4 sm:p-6"
							>
								<div class="flex items-center justify-between mb-3 flex-shrink-0">
									<h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ title }}</h3>
									<div class="flex items-center gap-1">
										<button
											type="button"
											class="p-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300 disabled:opacity-50"
											title="Export as PDF"
											:disabled="exporting"
											@click="exportToPdf(modalRoot?.$el ?? modalRoot)"
										>
											<ArrowDownTrayIcon class="h-5 w-5" />
										</button>
										<Menu as="div" class="relative">
											<MenuButton
												class="p-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300"
												title="Label options"
											>
												<EllipsisVerticalIcon class="h-5 w-5" />
											</MenuButton>
											<MenuItems
												class="absolute right-0 mt-1 w-40 origin-top-right rounded-md bg-white dark:bg-gray-800 shadow-lg ring-1 ring-black/5 dark:ring-white/10 focus:outline-none z-20"
											>
												<div class="py-1 text-xs uppercase tracking-wide text-gray-400 px-3 pt-2">Labels</div>
												<MenuItem
													v-for="opt in labelOptions"
													:key="opt.value"
													v-slot="{ active }"
												>
													<button
														type="button"
														class="w-full flex items-center justify-between px-3 py-1.5 text-sm text-gray-700 dark:text-gray-200"
														:class="active ? 'bg-gray-100 dark:bg-gray-700' : ''"
														@click="labelMode = opt.value"
													>
														<span>{{ opt.label }}</span>
														<CheckIcon v-if="labelMode === opt.value" class="h-4 w-4 text-indigo-600" />
													</button>
												</MenuItem>
											</MenuItems>
										</Menu>
										<button
											type="button"
											class="p-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300"
											@click="open = false"
										>
											<XMarkIcon class="h-5 w-5" />
										</button>
									</div>
								</div>
								<div class="flex-1 min-h-0">
									<slot name="expanded" :label-mode="labelMode">
										<slot :label-mode="labelMode" />
									</slot>
								</div>
							</DialogPanel>
						</TransitionChild>
					</div>
				</div>
			</Dialog>
		</TransitionRoot>
	</div>
</template>
