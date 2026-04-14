<script setup>
import { ref } from "vue";
import {
	Dialog,
	DialogPanel,
	TransitionChild,
	TransitionRoot,
} from "@headlessui/vue";
import RankDistributionChart from "@/Components/Charts/RankDistributionChart.vue";
import {
	ChevronDownIcon,
	ArrowsPointingOutIcon,
	XMarkIcon,
} from "@heroicons/vue/24/outline";

const props = defineProps({
	distribution: {
		type: Array,
		required: true,
	},
});

// Collapsed state - default expanded
const isCollapsed = ref(false);

// Fullscreen modal state
const showFullScreenModal = ref(false);

const toggleCollapse = () => {
	isCollapsed.value = !isCollapsed.value;
};

const openFullScreen = () => {
	showFullScreenModal.value = true;
};

const closeFullScreen = () => {
	showFullScreenModal.value = false;
};
</script>

<template>
	<section v-if="distribution && distribution.length > 0">
		<!-- Collapsible Header -->
		<button
			type="button"
			class="flex items-center justify-between w-full mb-4 group"
			@click="toggleCollapse"
		>
			<h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
				Rank Distribution
				<span class="text-sm font-normal text-gray-500 dark:text-gray-400">
					({{ distribution.length }} ranks)
				</span>
			</h2>
			<ChevronDownIcon
				class="h-5 w-5 text-gray-500 dark:text-gray-400 transition-transform duration-200 group-hover:text-gray-700 dark:group-hover:text-gray-300"
				:class="{ '-rotate-180': !isCollapsed }"
			/>
		</button>

		<!-- Collapsible Content -->
		<transition
			enter-active-class="transition-all duration-300 ease-out"
			enter-from-class="opacity-0 max-h-0"
			enter-to-class="opacity-100 max-h-[2000px]"
			leave-active-class="transition-all duration-200 ease-in"
			leave-from-class="opacity-100 max-h-[2000px]"
			leave-to-class="opacity-0 max-h-0"
		>
			<div
				v-show="!isCollapsed"
				class="grid grid-cols-1 lg:grid-cols-2 gap-6 overflow-hidden"
			>
				<!-- Chart with expand button -->
				<div class="relative">
					<RankDistributionChart
						:distribution="distribution"
						title="Ranks by Staff Count"
					/>
					<!-- Expand button -->
					<button
						type="button"
						class="absolute top-2 right-2 p-1.5 rounded-md bg-white/80 dark:bg-gray-700/80 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-white dark:hover:bg-gray-700 shadow-sm ring-1 ring-gray-900/10 dark:ring-gray-600 transition-colors"
						title="Expand chart"
						@click="openFullScreen"
					>
						<ArrowsPointingOutIcon class="h-5 w-5" />
					</button>
				</div>

				<!-- Details List -->
				<div
					class="bg-white dark:bg-gray-800 rounded-lg shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700 p-4"
				>
					<h3
						class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4"
					>
						Rank Details
					</h3>
					<div class="space-y-3 max-h-72 overflow-y-auto">
						<div
							v-for="(rank, index) in distribution"
							:key="rank.id"
							class="flex items-center justify-between py-2 px-4 border-b border-gray-100 dark:border-gray-700 last:border-0"
						>
							<div class="flex items-center gap-3">
								<span
									class="flex items-center justify-center w-6 h-6 text-xs font-medium rounded-full bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400"
								>
									{{ index + 1 }}
								</span>
								<div>
									<p
										class="text-sm font-medium text-gray-900 dark:text-gray-100"
									>
										{{ rank.name }}
									</p>
									<p
										v-if="rank.full_name !== rank.name"
										class="text-xs text-gray-500 dark:text-gray-400"
									>
										{{ rank.full_name }}
									</p>
								</div>
							</div>
							<span
								class="text-sm font-semibold text-violet-600 dark:text-violet-400"
							>
								{{ rank.count?.toLocaleString() }}
							</span>
						</div>
					</div>
				</div>
			</div>
		</transition>

		<!-- Fullscreen Modal -->
		<TransitionRoot as="template" :show="showFullScreenModal">
			<Dialog as="div" class="relative z-50" @close="closeFullScreen">
				<TransitionChild
					as="template"
					enter="ease-out duration-300"
					enter-from="opacity-0"
					enter-to="opacity-100"
					leave="ease-in duration-200"
					leave-from="opacity-100"
					leave-to="opacity-0"
				>
					<div
						class="fixed inset-0 bg-gray-900/80 dark:bg-gray-900/90 transition-opacity"
					/>
				</TransitionChild>

				<div class="fixed inset-0 z-50 overflow-y-auto">
					<div class="flex min-h-full items-center justify-center p-4">
						<TransitionChild
							as="template"
							enter="ease-out duration-300"
							enter-from="opacity-0 scale-95"
							enter-to="opacity-100 scale-100"
							leave="ease-in duration-200"
							leave-from="opacity-100 scale-100"
							leave-to="opacity-0 scale-95"
						>
							<DialogPanel
								class="relative w-full max-w-7xl transform rounded-xl bg-white dark:bg-gray-800 shadow-2xl transition-all"
							>
								<!-- Modal Header -->
								<div
									class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700"
								>
									<h2
										class="text-xl font-semibold text-gray-900 dark:text-gray-100"
									>
										Rank Distribution
										<span
											class="text-sm font-normal text-gray-500 dark:text-gray-400"
										>
											({{ distribution.length }} ranks)
										</span>
									</h2>
									<button
										type="button"
										class="rounded-md p-2 text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
										@click="closeFullScreen"
									>
										<XMarkIcon class="h-6 w-6" />
									</button>
								</div>

								<!-- Modal Content -->
								<div class="p-6">
									<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
										<!-- Large Chart -->
										<div
											class="xl:col-span-2 bg-white dark:bg-gray-800 rounded-lg ring-1 ring-gray-900/5 dark:ring-gray-700 p-4"
										>
											<div
												:class="
													distribution.length < 11 ? 'h-[500px]' : 'h-[650px]'
												"
											>
												<RankDistributionChart
													:distribution="distribution"
													title="Ranks by Staff Count"
												/>
											</div>
										</div>

										<!-- Details List -->
										<div
											class="bg-gray-50 dark:bg-gray-900/50 rounded-lg ring-1 ring-gray-900/5 dark:ring-gray-700 p-4"
										>
											<h3
												class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4"
											>
												All Ranks
											</h3>
											<div
												:class="
													distribution.length < 11
														? 'max-h-[460px]'
														: 'max-h-[600px]'
												"
												class="space-y-2 overflow-y-auto"
											>
												<div
													v-for="(rank, index) in distribution"
													:key="rank.id"
													class="flex items-center justify-between py-2 px-4 rounded-md hover:bg-white dark:hover:bg-gray-800 transition-colors"
												>
													<div class="flex items-center gap-3">
														<span
															class="flex items-center justify-center w-7 h-7 text-xs font-medium rounded-full bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400"
														>
															{{ index + 1 }}
														</span>
														<div>
															<p
																class="text-sm font-medium text-gray-900 dark:text-gray-100"
															>
																{{ rank.name }}
															</p>
															<p
																v-if="rank.full_name !== rank.name"
																class="text-xs text-gray-500 dark:text-gray-400"
															>
																{{ rank.full_name }}
															</p>
														</div>
													</div>
													<span
														class="text-sm font-semibold text-violet-600 dark:text-violet-400"
													>
														{{ rank.count?.toLocaleString() }}
													</span>
												</div>
											</div>
										</div>
									</div>
								</div>
							</DialogPanel>
						</TransitionChild>
					</div>
				</div>
			</Dialog>
		</TransitionRoot>
	</section>
</template>
