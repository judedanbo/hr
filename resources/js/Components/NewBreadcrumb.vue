<script setup>
import { Link } from '@inertiajs/inertia-vue3'
import { ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/20/solid'
defineProps({
  links: {
    type: Array,
    required: false,
    default: () => []
  }
})
</script>
<template>
    <nav class="hidden sm:flex" aria-label="Breadcrumb">
          <ol role="list" class="flex items-center space-x-4">
            <li v-for="link in links">
              <div class="flex">
                <Link :href="link.url" class="text-sm font-medium text-gray-400 hover:text-gray-200">{{ link.name }}</Link>
              </div>
            </li>
            <!-- <li>
              <div class="flex items-center">
                <ChevronRightIcon class="h-5 w-5 flex-shrink-0 text-gray-500" aria-hidden="true" />
                <Link href="#" class="ml-4 text-sm font-medium text-gray-400 hover:text-gray-200">Engineering</Link>
              </div>
            </li>
            <li>
              <div class="flex items-center">
                <ChevronRightIcon class="h-5 w-5 flex-shrink-0 text-gray-500" aria-hidden="true" />
                <Link href="#" aria-current="page" class="ml-4 text-sm font-medium text-gray-400 hover:text-gray-200">Back End Developer</Link>
              </div>
            </li> -->
          </ol>
        </nav>
</template>