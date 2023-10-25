<script setup>
let buttonClicked = () => {};

defineProps({
	tabs: Array,
});
</script>
<template>
	<div class="flex border-b border-gray-200 dark:border-gray-700">
		<template v-for="(tab, index) in tabs" :key="index">
			<button
				@click="buttonClicked(tab)"
				class="flex items-center h-10 px-2 py-2 -mb-px text-center bg-transparent sm:px-4 -px-1 border-b-2 whitespace-nowrap focus:outline-none"
				:class="
					tab.active
						? 'text-green-600  border-green-500 dark:border-green-400 dark:text-green-300'
						: 'text-gray-700 dark:text-white cursor-base  hover:border-gray-400'
				"
			>
				<!-- <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="w-4 h-4 mx-1 sm:w-6 sm:h-6"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"
                    />
                </svg> -->

				<span class="mx-1 text-sm sm:text-base">
					{{ tab.name }}
				</span>
			</button>
		</template>
	</div>
</template>
