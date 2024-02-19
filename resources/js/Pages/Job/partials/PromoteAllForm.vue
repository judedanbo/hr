<script setup>
import { Inertia } from "@inertiajs/inertia";
import { ref, onMounted } from "vue";

const props = defineProps({
	rank: Number,
});

const emit = defineEmits(["formSubmitted"]);

const nextRank = ref([]);
onMounted(async () => {
	const next = await axios.get(route("rank.next", { rank: props.rank }));
	nextRank.value = next.data;
	console.log(nextRank.value);
});

const submitHandler = (data, node) => {
	Inertia.post(route("staff.store"), data, {
		preserveState: true,
		onSuccess: () => {
			node.reset();
			emit("formSubmitted");
		},
		onError: (errors) => {
			node.setErrors(["there are errors in the form"], errors);
		},
	});
};
</script>
<template>
	<main class="bg-gray-100 dark:bg-gray-700">
		<h1
			class="text-2xl font-semibold tracking-wider text-green-800 dark:text-gray-50 px-10"
		>
			Add new Staff
		</h1>
		<FormKit
			id="promoteAll"
			type="form"
			name="promoteAll"
			value="formData"
			submit-label="Promote All Staff"
			:actions="false"
			wrapper-class="mx-auto"
			@submit="submitHandler"
		>
			<h1
				class="mb-4 font-semibold tracking-wider text-lg text-green-800 dark:text-gray-200"
			>
				Promote Selected Staff
				<FormKit
					v-if="nextRank.length > 0"
					id="rank_id"
					v-model="nextRank[0]"
					type="select"
					name="rank_id"
					validation="required|integer|min:1|max:150"
					label="Rank"
					:options="nextRank"
					placeholder="Select new Rank"
					error-visibility="submit"
				/>
				<FormKit
					v-else
					id="rank_id"
					type="select"
					name="rank_id"
					validation="required|integer|min:1|max:150"
					label="Rank"
					:options="nextRank"
					placeholder="Select new Rank"
					error-visibility="submit"
				/>
			</h1>
		</FormKit>
	</main>
</template>
