<script setup>
import { onMounted, ref } from "vue";

const props = defineProps({
	userRoles: {
		type: Array,
		default: () => [],
	},
});
const selectedRoles = ref([]);
let roles = ref([]);

onMounted(async () => {
	const response = await axios.get(route("roles.list"));
	roles.value = response.data;
});
</script>
<template>
	<!-- {{ userRoles }} -->
	<div>
		<FormKit
			type="checkbox"
			name="roles"
			id="roles"
			validation="required|integer|min:1|max:2000"
			label="New role"
			placeholder="Select new Rank"
			:options="roles"
			error-visibility="submit"
		/>
		<!-- <fieldset id="roles" name="roles">
			<label v-for="role in roles" :key="role.value" :for="role.value">
				<input
					:id="role.value"
					:name="role.value"
					:value="role.value"
					type="checkbox"
					v-model="role.value"
				/>
				{{ role.label }}
			</label>
		</fieldset> -->
	</div>
</template>

<style>
.formkit-decorator {
	@apply peer-checked:bg-green-500;
}
</style>
