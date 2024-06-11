<script setup>
import axios from "axios";
import { onMounted, ref } from "vue";
let props = defineProps({
	person: { type: Number, required: true },
});
let roles = ref(null);
onMounted(async () => {
	roles.value = await axios.get(route("person-roles.dependent", props.person));
});
</script>
<template>
	<div v-if="roles">
		<!-- TODO link to role type show page -->
		<!-- {{ roles.data }} -->
		<div
			v-if="roles.data"
			class="inline-flex items-center gap-x-1.5 rounded-md px-6 bg-purple-50 py-6 text-xs font-medium text-purple-700"
		>
			{{ roles.data }}
		</div>
	</div>
</template>
