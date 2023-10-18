<script setup>
import { ref, onMounted } from "vue";
import { CheckCircleIcon, XMarkIcon } from "@heroicons/vue/20/solid";
// import { useToggle } from "@vueuse/core";
const props = defineProps({
    alert: {
        type: String,
        default: "Alert",
    },
    type: {
        type: String,
        default: "success",
    },
});
const emit = defineEmits(["close"]);
const show = ref(true);
// const toggle = useToggle(show);

onMounted(() => {
    setTimeout(() => {
        emit("close");
    }, 3000);
});
</script>
<template>
    <div class="rounded-md bg-green-50 p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <CheckCircleIcon
                    :class="
                        type === 'success'
                            ? 'h-5 w-5 text-green-400'
                            : 'h-5 w-5 text-rose-400'
                    "
                    class="h-5 w-5"
                    aria-hidden="true"
                />
            </div>
            <div class="ml-3">
                <p
                    :class="
                        type === 'success' ? 'text-green-800' : 'text-rose-800'
                    "
                    class="text-sm font-medium"
                >
                    {{ alert }}
                </p>
            </div>
            <div class="ml-auto pl-3">
                <div class="-mx-1.5 -my-1.5">
                    <button
                        type="button"
                        :class="
                            type === 'success'
                                ? 'bg-green-50 text-green-500 hover:bg-green-100 focus:ring-2 focus:ring-green-600 focus:ring-offset-2 focus:ring-offset-green-50'
                                : 'bg-rose-50 text-rose-500 hover:bg-rose-100 focus:ring-2 focus:ring-rose-600 focus:ring-offset-2 focus:ring-offset-rose-50'
                        "
                        class="inline-flex rounded-md p-1.5 focus:outline-none focus:ring-2 focus:ring-offset-2"
                    >
                        <span class="sr-only">Dismiss</span>
                        <XMarkIcon
                            @click="emit('close')"
                            class="h-5 w-5"
                            aria-hidden="true"
                        />
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
