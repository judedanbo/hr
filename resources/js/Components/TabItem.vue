<script setup>
defineProps({
	tab: Array,
});
</script>
<template>
	<div class="flex border-b border-gray-200 dark:border-gray-700">
		<template v-for="(tab, index) in tabs" :key="index">
			<button
				class="flex items-center h-10 px-2 py-2 -mb-px text-center text-green-600 bg-transparent border-b-2 border-green-500 sm:px-4 -px-1 dark:border-green-400 dark:text-green-300 whitespace-nowrap focus:outline-none"
			>
				<svg
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
				</svg>

				<span class="mx-1 text-sm sm:text-base">
					{{ tab.name }}
				</span>
			</button>
		</template>

		<!-- <button
            class="flex items-center h-10 px-2 py-2 -mb-px text-center text-gray-700 bg-transparent border-b-2 border-transparent sm:px-4 -px-1 dark:text-white whitespace-nowrap cursor-base focus:outline-none hover:border-gray-400"
        >
            <BadgeCheckIcon class="w-5 h-5" />

            <span class="mx-1 text-sm sm:text-base"> Account </span>
        </button> -->

		<!-- <button
            class="flex items-center h-10 px-2 py-2 -mb-px text-center text-gray-700 bg-transparent border-b-2 border-transparent sm:px-4 -px-1 dark:text-white whitespace-nowrap cursor-base focus:outline-none hover:border-gray-400"
        >
            <svg
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
                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
                />
            </svg>

            <span class="mx-1 text-sm sm:text-base"> Notification </span>
        </button> -->
	</div>
</template>
