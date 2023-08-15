<script setup>
import { Inertia } from "@inertiajs/inertia";
import { Link } from "@inertiajs/inertia-vue3";

let props = defineProps({
  title: String,
  value: { type: Number, default: null },
  link: String,
});

let details = (url = props.link) => {
  Inertia.get(props.link);
};
</script>
<template>
  <div class="mx-auto flex max-w-xs flex-col gap-y-4 w-20 justify-center items-center ">
    <dt class="text-base leading-7 text-gray-600 dark:text-gray-400">
      {{ title }}
    </dt>
    <dd
      class="order-first text-3xl font-semibold tracking-tight text-gray-900 dark:text-gray-50 sm:text-5xl"
    >
      {{ value.toLocaleString() }}
    </dd>
  </div>
</template>
