<script setup>
import Avatar from "@/Components/Avatar.vue";

defineProps({
  notes: Array,
});

</script>
<template>
  <!-- New note form -->

  <ul role="list" class="pt-4">
    <li
      v-for="(note, noteIdx) in notes"
      :key="note.id"
      class="relative flex gap-x-4"
    >
      <div
        :class="[
          noteIdx === notes.length - 1 ? 'h-6' : '-bottom-6',
          'absolute left-0 top-0 flex w-6 justify-center',
        ]"
      >
        <div class="w-px bg-gray-200" />
      </div>
      <!-- <img :src="note.person" alt="" class="relative mt-3 h-6 w-6 flex-none rounded-full bg-gray-50" /> -->
      <Avatar :person_id="note.created_by" />
      <div
        class="flex-auto rounded-md py-1 px-3 ring-1 ring-inset ring-gray-100"
      >
        <div class="flex justify-end gap-x-4">
          <time
            :datetime="note.note_date_time"
            class="flex-none py-0.5 text-xs leading-5 text-gray-500"
            >{{ note.note_date }}</time
          >
        </div>
        <p class="text-sm leading-6 text-gray-500">{{ note.note }}</p>
      </div>
    </li>
  </ul>
</template>
