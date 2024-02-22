<template>
    <div>
      <div class="relative mt-2 rounded-md shadow-sm">
        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
          <MagnifyingGlassIcon class="h-5 w-5 text-green-500" aria-hidden="true" />
        </div>
        <input v-model="search" type="search" name="search" id="search" class="block w-full rounded-md border-0 py-1.5 pl-10 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-green-600 sm:text-sm sm:leading-6" placeholder="search ..." autofocus @input="emit('search', search)"/>
      </div>
    </div>
  </template>
  
  <script setup>
  import { ref } from 'vue'
  import { MagnifyingGlassIcon } from '@heroicons/vue/20/solid'
  const emit = defineEmits(["search"]);

  defineProps({
    search: {
      type: String,
      required: false,
      default: ''
    }
  })
  </script>