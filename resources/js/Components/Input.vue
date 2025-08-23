<script setup>
import { ref } from "vue";

defineProps({
	modelValue: String,
	alignText: {
		type: String,
		default: "left",
	},
	name: String,
	prefix: {
		type: String,
		default: null,
	},
	postfix: {
		type: String,
		default: null,
	},
	placeholder: String,
	type: {
		type: String,
		default: "text",
	},
	autofocus: {
		type: Boolean,
		default: false,
	},
	required: {
		type: Boolean,
		default: false,
	},
	autocomplete: {
		type: String,
		default: null,
	},
	ariaDescribedby: {
		type: String,
		default: null,
	},
	ariaLabel: {
		type: String,
		default: null,
	},
});

defineEmits(["update:modelValue"]);

const input = ref(null);
</script>

<template>
	<div>
		<div class="mt-2">
			<div
				class="flex items-center rounded-md bg-white px-3 ring-1 ring-insert ring-gray-400 dark:focus-within:ring-gray-300 focus-within:ring-2 focus-within:ring-insert focus-within:ring-green-600 dark:bg-gray-700"
			>
				<div
					v-if="prefix"
					class="shrink-0 select-none text-base text-gray-500 sm:text-sm/6"
				>
					{{ prefix }}
				</div>
				<input
					:value="modelValue"
					:type="type"
					:autofocus="autofocus"
					:required="required"
					:autocomplete="autocomplete"
					ref="input"
					:aria-describedby="ariaDescribedby ?? name"
					:aria-label="ariaLabel ?? name"
					class="block min-w-0 grow py-1.5 pl-1 pr-3 text-base text-gray-900 dark:text-gray-50 shadow-sm placeholder:text-gray-400 sm:text-sm/6 border-none ring-0 focus:ring-0 dark:bg-gray-700"
					:class="'text-' + alignText"
					@input="$emit('update:modelValue', $event.target.value)"
					:placeholder="placeholder ?? name"
				/>
				<div
					v-if="postfix"
					id="price-currency"
					class="shrink-0 select-none text-base text-gray-500 dark:text-gray-50 sm:text-sm/6"
				>
					{{ postfix }}
				</div>
			</div>
		</div>
	</div>
</template>
