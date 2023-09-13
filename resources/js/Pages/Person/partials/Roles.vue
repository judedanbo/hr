<script setup>
import axios from "axios";
import { Link } from "@inertiajs/inertia-vue3";
import { onMounted, ref } from "vue";
let props = defineProps({
  person: Number,
});
let roles = ref(null);
onMounted(async () => {
  roles.value = await axios.get(route("person-roles.show", props.person));
});
</script>
<template>
  <div v-if="roles">
    <!-- TODO link to role type show page -->
    <span v-if="roles.data.staff.length > 0" >
      <span
        v-if="roles.data.staff[0].status == 'Active staff'"
        class="inline-flex items-center gap-x-1.5 rounded-full bg-green-100 dark:bg-gray-300/20 px-2 py-1 text-xs font-medium text-green-700 dark:text-gray-200"
      >
        <svg
          class="h-1.5 w-1.5 fill-green-500"
          viewBox="0 0 6 6"
          aria-hidden="true"
        >
          <circle cx="3" cy="3" r="3" />
        </svg>
        {{ roles.data.staff[0].status }}
      </span>
      <span
        v-else
        class="inline-flex items-center gap-x-1.5 rounded-full bg-yellow-100 dark:bg-gray-300/20 px-2 py-1 text-xs font-medium text-yellow-700 dark:text-gray-200"
      >
        <svg
          class="h-1.5 w-1.5 fill-yellow-500"
          viewBox="0 0 6 6"
          aria-hidden="true"
        >
          <circle cx="3" cy="3" r="3" />
        </svg>
        {{ roles.data.staff[0].status }}
      </span>
    </span>

    <span
      v-if="roles.data.user"
      class="inline-flex items-center gap-x-1.5 rounded-full bg-red-100 dark:bg-gray-300/20 px-2 py-1 text-xs font-medium text-red-700 dark:text-gray-200"
    >
      <svg
        class="h-1.5 w-1.5 fill-red-500"
        viewBox="0 0 6 6"
        aria-hidden="true"
      >
        <circle cx="3" cy="3" r="3" />
      </svg>
      User
    </span>
    <span
      v-if="roles.data.dependent"
      class="inline-flex items-center gap-x-1.5 rounded-full bg-purple-100 px-2 py-1 text-xs font-medium text-purple-700"
    >
      <svg
        class="h-1.5 w-1.5 fill-purple-500"
        viewBox="0 0 6 6"
        aria-hidden="true"
      >
        <circle cx="3" cy="3" r="3" />
      </svg>
      Dependent
    </span>
  </div>
</template>
