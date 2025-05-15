<script setup lang="ts">
interface Item {
	id: number | string;
	value: string;
	checked: boolean;
	label: string;
}

const props = defineProps<{
	items: Item[];
	checked: boolean;
}>();
const emit = defineEmits<{
	(e: "update:checked", value: boolean): void;
}>();
const handleChange = (event: Event) => {
	const target = event.target as HTMLInputElement;
	emit("update:checked", target.checked);
};
</script>

<template>
	<fieldset v-for="item in props.items" :key="item.id" class="space-y-5">
		<legend class="sr-only">{{ item.label }}</legend>
		<div class="space-y-5">
			<div class="flex gap-3">
				<div class="flex h-6 shrink-0 items-center">
					<div class="group grid size-4 grid-cols-1">
						<input
							:id="item.value"
							:aria-describedby="item.label"
							:name="item.value"
							type="checkbox"
							:checked="item.checked"
							class="col-start-1 row-start-1 appearance-none rounded border border-gray-300 bg-white checked:border-green-600 checked:bg-green-600 indeterminate:border-green-600 indeterminate:bg-green-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600 disabled:border-gray-300 disabled:bg-gray-100 disabled:checked:bg-gray-100 forced-colors:appearance-auto"
						/>
						<svg
							class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-white group-has-[:disabled]:stroke-gray-950/25"
							viewBox="0 0 14 14"
							fill="none"
						>
							<path
								class="opacity-0 group-has-[:checked]:opacity-100"
								d="M3 8L6 11L11 3.5"
								stroke-width="2"
								stroke-linecap="round"
								stroke-linejoin="round"
							/>
							<path
								class="opacity-0 group-has-[:indeterminate]:opacity-100"
								d="M3 7H11"
								stroke-width="2"
								stroke-linecap="round"
								stroke-linejoin="round"
							/>
						</svg>
					</div>
				</div>
				<div class="text-sm/6">
					<label
						for="comments"
						class="font-medium dark:text-white text-gray-900"
						>{{ item.value }}</label
					>
				</div>
			</div>
		</div>
	</fieldset>
</template>
