<script setup>
import RankDistributionChart from "@/Components/Charts/RankDistributionChart.vue";

const props = defineProps({
	distribution: {
		type: Array,
		required: true,
	},
});

const emit = defineEmits(["chart-click"]);

function handleChartClick(data) {
	emit("chart-click", data);
}
</script>

<template>
	<section v-if="distribution && distribution.length > 0">
		<h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
			Rank Distribution
		</h2>
		<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
			<RankDistributionChart
				:distribution="distribution"
				title="Top 10 Ranks by Staff Count"
				@bar-click="handleChartClick"
			/>
			<div
				class="bg-white dark:bg-gray-800 rounded-lg shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700 p-4"
			>
				<h3
					class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4"
				>
					Rank Details
				</h3>
				<div class="space-y-3">
					<div
						v-for="(rank, index) in distribution"
						:key="rank.id"
						class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700 last:border-0 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50 -mx-2 px-2 rounded transition-colors"
						@click="
							handleChartClick({
								filter: 'rank',
								params: { id: rank.id },
								title: `${rank.full_name} Staff`,
							})
						"
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
	</section>
</template>
