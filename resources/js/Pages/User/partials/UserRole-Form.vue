<script setup>
import { onMounted, ref } from "vue";

const userRoles = ref([]);
let roles = ref([]);

onMounted(async () => {
	const response = await axios.get(route("roles.list"));
	roles.value = response.data;
});
</script>
<template>
	<div>
		<FormKit type="hidden" id="user" name="user" :value="user" />
		<FormKit
			v-model="userRoles"
			type="checkbox"
			name="roles"
			id="roles"
			validation="required|integer|min:1|max:2000"
			label="New role"
			placeholder="Select new Rank"
			:options="roles"
			error-visibility="submit"
		/>
	</div>
</template>
