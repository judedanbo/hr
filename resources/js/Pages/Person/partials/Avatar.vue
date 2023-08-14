<script setup>
defineProps({
  initials: String,
  imageUrl: String,
});
</script>

<template>
  <div>
    <img v-if="imageUrl"
      class="h-12 w-12 flex-none rounded-full bg-gray-50"
      :src="imageUrl"
      alt=""
    />
    <div v-else
      class="w-12 h-12 rounded-full bg-gray-400 dark:bg-gray-100 flex justify-center items-center "
    >
      <h1 class="text-white dark:text-gray-900 font-bold tracking-widest">
        {{ initials }}
      </h1>
    </div>
  </div>
</template>
