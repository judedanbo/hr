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
import { Chart } from "chart.js";

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

// Deep-clone preserving function references (datalabels formatters, tooltip
// callbacks) — unlike structuredClone, which throws on functions, or JSON
// round-trip, which silently strips them.
function cloneDeep(src) {
	if (src === null || typeof src !== "object") return src;
	if (Array.isArray(src)) return src.map(cloneDeep);
	const out = {};
	for (const k of Object.keys(src)) out[k] = cloneDeep(src[k]);
	return out;
}

function ensure(obj, path) {
	let node = obj;
	for (const key of path) {
		if (node[key] === undefined || node[key] === null) node[key] = {};
		node = node[key];
	}
	return node;
}

// Render the live chart into a large offscreen canvas so category labels,
// legend entries, and datalabels are captured at a resolution where they
// don't get truncated or culled by Chart.js's auto-layout.
async function renderChartAtSize(liveChart, width, height) {
	const host = document.createElement("div");
	host.style.cssText = `position:fixed;left:-100000px;top:0;width:${width}px;height:${height}px;background:#ffffff;`;
	const canvas = document.createElement("canvas");
	canvas.width = width;
	canvas.height = height;
	host.appendChild(canvas);
	document.body.appendChild(host);

	const data = cloneDeep(liveChart.config.data);
	const options = cloneDeep(liveChart.config.options ?? {});
	options.responsive = false;
	options.maintainAspectRatio = false;
	options.animation = false;
	options.devicePixelRatio = 2;

	// Force dark-mode tick / legend / title / datalabel colors back to a
	// dark tone since the PDF background is white.
	const DARK = "rgba(0,0,0,0.8)";
	const MUTED = "rgba(0,0,0,0.6)";

	// Force the legend on (several charts hide it inline to save space) and
	// ensure readable font sizing at print resolution.
	const legend = ensure(options, ["plugins", "legend"]);
	if (legend.display !== true) legend.display = true;
	const legendLabels = ensure(options, ["plugins", "legend", "labels"]);
	legendLabels.font = { size: 16, weight: "600", ...(legendLabels.font ?? {}) };
	legendLabels.color = DARK;
	legendLabels.padding = legendLabels.padding ?? 12;

	// Title: slightly larger than default.
	const title = ensure(options, ["plugins", "title"]);
	title.font = { size: 20, weight: "bold", ...(title.font ?? {}) };
	title.color = DARK;

	// Datalabels: bump up so they're readable.
	const dl = ensure(options, ["plugins", "datalabels"]);
	dl.font = { size: 16, weight: "bold", ...(dl.font ?? {}) };

	// Axis ticks: bump up so category labels are visible.
	if (options.scales) {
		for (const axisKey of Object.keys(options.scales)) {
			const axis = options.scales[axisKey];
			const ticks = (axis.ticks = axis.ticks ?? {});
			ticks.font = { size: 14, ...(ticks.font ?? {}) };
			ticks.color = MUTED;
			if (axis.grid) axis.grid.color = "rgba(0,0,0,0.08)";
			// Prevent Chart.js from skipping category labels at high res.
			if (ticks.autoSkip === undefined) ticks.autoSkip = false;
			if (ticks.maxRotation === undefined) ticks.maxRotation = 0;
		}
	}

	const bigChart = new Chart(canvas, {
		type: liveChart.config.type,
		data,
		options,
		plugins: liveChart.config.plugins ?? [],
	});

	// One frame to layout + render.
	await new Promise((r) => requestAnimationFrame(() => requestAnimationFrame(r)));

	// Composite onto a white-backed canvas to avoid transparent PNG + dark mode.
	const composite = document.createElement("canvas");
	composite.width = canvas.width;
	composite.height = canvas.height;
	const ctx = composite.getContext("2d");
	ctx.fillStyle = "#ffffff";
	ctx.fillRect(0, 0, composite.width, composite.height);
	ctx.drawImage(canvas, 0, 0);
	const dataUrl = composite.toDataURL("image/png");

	bigChart.destroy();
	document.body.removeChild(host);
	return { dataUrl, width, height };
}

async function exportToPdf(source) {
	const canvas = source?.querySelector("canvas");
	if (!canvas) return;
	const liveChart = Chart.getChart(canvas);
	if (!liveChart) return;

	exporting.value = true;
	try {
		// A4 landscape at 200 DPI ≈ 2339x1654; render at 2200x1400 with
		// devicePixelRatio 2 so the embedded PNG stays crisp at print scale.
		const { dataUrl, width, height } = await renderChartAtSize(
			liveChart,
			2200,
			1400,
		);

		const pdf = new jsPDF({
			orientation: width >= height ? "landscape" : "portrait",
			unit: "pt",
			format: "a4",
		});

		const pageWidth = pdf.internal.pageSize.getWidth();
		const pageHeight = pdf.internal.pageSize.getHeight();
		const margin = 36;
		const headerHeight = 48;
		const footerHeight = 24;

		pdf.setFont("helvetica", "bold");
		pdf.setFontSize(14);
		pdf.text(props.title, margin, margin + 16);

		pdf.setFont("helvetica", "normal");
		pdf.setFontSize(9);
		pdf.setTextColor(120);
		pdf.text(`Generated ${new Date().toLocaleString()}`, margin, margin + 32);
		pdf.setTextColor(0);

		const availW = pageWidth - margin * 2;
		const availH = pageHeight - margin * 2 - headerHeight - footerHeight;
		const ratio = width / height;
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
