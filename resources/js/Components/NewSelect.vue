<script setup>
import { ref, computed } from "vue";
import {
	Listbox,
	ListboxButton,
	ListboxLabel,
	ListboxOption,
	ListboxOptions,
} from "@headlessui/vue";
import { CheckIcon, ChevronUpDownIcon } from "@heroicons/vue/20/solid";
const emit = defineEmits(["update:modelValue"]);
const props = defineProps({
	options: Array,
	modelValue: [String, Number, Array],
	listLabel: String,
	placeholder: {
		type: String,
		default: "Select Option",
	},
	multiple: Boolean,
});

// const selectedOption = ref(props.options[0].value);
const label = computed(() => {
	return props.options.find((options) => options.value === props.modelValue)
		?.label;
	//   return props.options
	//     .filter((option) => {
	//       if (Array.isArray(props.modelValue)) {
	//         return props.modelValue.includes(option.value);
	//       }

	//       return props.modelValue === option.value;
	//     })
	//     .map((option) => option.label)
	//     .join(", ");
});
</script>
<template>
	<Listbox
		as="div"
		model-value="props.modelValue"
		class="w-full"
		@update:modelValue="(value) => emit('update:modelValue', value)"
	>
		<ListboxLabel
			v-if="listLabel"
			class="block text-sm font-medium leading-6 text-gray-900"
			>{{ listLabel }}</ListboxLabel
		>
		<div class="relative mt-2">
			<ListboxButton
				class="relative w-full cursor-default rounded-md bg-green-50 dark:bg-gray-600 py-1.5 pl-3 pr-10 text-left text-gray-900 dark:text-gray-50 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 focus:outline-none focus:ring-2 focus:ring-green-600 focus:dark:ring-gray-300 sm:text-sm sm:leading-6"
			>
				<span v-if="placeholder" class="block truncate">{{ label }}</span>
				<span v-else class="block truncate">Select Option</span>
				<span
					class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2"
				>
					<ChevronUpDownIcon class="h-5 w-5 text-gray-400" aria-hidden="true" />
				</span>
			</ListboxButton>

			<transition
				leave-active-class="transition ease-in duration-100"
				leave-from-class="opacity-100"
				leave-to-class="opacity-0"
			>
				<ListboxOptions
					class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm"
				>
					<ListboxOption
						v-for="option in options"
						:key="option.value"
						as="template"
						:value="option.value"
					>
						<li
							:class="[
								props.modelValue == option.value
									? 'bg-green-700 dark:bg-gray-800 text-green-100 dark:text-gray-50 '
									: 'text-gray-900 bg-green-50/50 dark:bg-gray-500 dark:text-gray-50 ',
								'relative cursor-default select-none py-2 pl-3 pr-9 hover:bg-green-600/80 dark:hover:bg-gray-600 hover:text-gray-50',
							]"
						>
							<span
								:class="[
									props.modelValue == option.value
										? 'font-semibold'
										: 'font-normal',
									'block truncate',
								]"
								>{{ option.label }}</span
							>

							<span
								v-if="props.modelValue == option.value"
								:class="[
									props.modelValue == option.value
										? 'text-white'
										: 'text-green-600',
									'absolute inset-y-0 right-0 flex items-center pr-4',
								]"
							>
								<CheckIcon class="h-5 w-5" aria-hidden="true" />
							</span>
						</li>
					</ListboxOption>
				</ListboxOptions>
			</transition>
		</div>
	</Listbox>
</template>
