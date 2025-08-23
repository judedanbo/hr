<script setup>
import { router } from "@inertiajs/vue3";
import { ref, onMounted, computed } from "vue";
import { format } from "date-fns";
const props = defineProps({
	rank: Number,
	formErrors: {
		type: Object,
		default: () => {},
	},
});
const today = format(new Date(), "yyyy-MM-dd");

const displayErrors = computed(() => {
	const start_date = props.formErrors["promoteAll.start_date"];
	const rank_id = props.formErrors["promoteAll.rank_id"];
	//return start_date;
	return { start_date, rank_id };
});

const emit = defineEmits(["formSubmitted", "unitSelected"]);

const nextRank = ref([]);
onMounted(async () => {
	const next = await axios.get(route("rank.next", { rank: props.rank }));
	nextRank.value = next.data;
});

const submitHandler = (data, node) => {
	emit("unitSelected", data);
	// router.post(route("staff.promote.all"), data, {
	// 	preserveState: true,
	// 	onSuccess: () => {
	// 		node.reset();
	// 		emit("formSubmitted");
	// 	},
	// 	onError: (errors) => {
	// // 		node.setErrors(["there are errors in the form"], errors);
	// 	},
	// });
	// if(displayErrors?.value?.start_date || displayErrors?.value?.rank_id){
	// 	node.setErrors(["there are errors in the form"], displayErrors);
	// }
};
const form = ref({
	rank_id: null,
	start_date: null,
});
</script>
<template>
	<main class="bg-gray-100 dark:bg-gray-700">
		<h1
			class="text-2xl font-semibold tracking-wider text-green-800 dark:text-gray-50 px-10"
		>
			Promote Staff
		</h1>
		<FormKit
			id="promoteAll"
			type="form"
			name="promoteAll"
			value="promoteAllData"
			submit-label="Promote All Staff"
			wrapper-class="mx-auto"
			@submit="submitHandler"
		>
			<h1
				class="mb-4 font-semibold tracking-wider text-lg text-green-800 dark:text-gray-200"
			>
				Promote Selected Staff
			</h1>
			<FormKit
				v-if="nextRank.length > 0"
				id="rank_id"
				:value="nextRank[0].value"
				type="select"
				name="rank_id"
				label="Rank"
				:options="nextRank"
				:errors="displayErrors?.rank_id ? [displayErrors?.rank_id] : []"
				placeholder="Select new Rank"
				error-visibility="submit"
				disabled
			/>
			{{ displayErrors?.rank_id }}
			<!-- <FormKit
					v-else
					id="rank_id"
					type="select"
					name="rank_id"
					validation="required|integer|min:1|max:150"
					label="Rank"
					:options="nextRank"
					placeholder="Select new Rank"
					error-visibility="submit"
				/> -->
			<FormKit
				id="start_date"
				type="date"
				name="start_date"
				:value="today"
				:max="today"
				label="Start date"
				:errors="displayErrors?.start_date ? [displayErrors?.start_date] : []"
				validation-visibility="submit"
				outer-class="flex-1"
			/>
			<!-- <p class="text-sm text-rose-500">{{ displayErrors?.start_date }}</p>  -->
		</FormKit>
		<!-- {{ displayErrors }} -->
	</main>
</template>
