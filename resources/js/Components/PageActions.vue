<script setup>
defineProps({
  actions: {
    type: Array,
    required: false,
    default: () => []
  }
})
const emit = defineEmits(["buttonClicked"]);
</script>
<template>
    <div class="mt-4 flex md:ml-4 md:mt-0">
      <button v-for="action in actions" :key="action.name" type="button" @click.prevent="emit('buttonClicked', action.name)"
        :class="action.color == 'primary' ? 'bg-green-600 text-white hover:bg-green-700 focus-visible:outline-green-600' : 'bg-white text-gray-900 hover:bg-gray-50'"
       class="ml-3 inline-flex items-center rounded-md  px-3 py-2 text-sm font-semibold  shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2">
       <component :is="action.icon" class="-ml-1 mr-1 h-5 w-5 flex-shrink-0" aria-hidden="true" />
       {{ action.name }}</button>
    </div>
</template>