<script setup>
import { CheckIcon } from "@heroicons/vue/20/solid";
import { onMounted, ref } from "vue";

const props = defineProps({
	userRoles: {
		type: Array,
		default: () => [],
	},
});
let roles = ref([]);
const localUserRoles = ref([...props.userRoles]);
onMounted(async () => {
	const response = await axios.get(route("roles.list"));
	roles.value = response.data;
});
</script>
<template>
	<!-- {{ userRoles }} -->
	<div>
		<FormKit
			id="roles"
			v-model="localUserRoles"
			type="checkbox"
			name="roles"
			validation="required|integer|min:1|max:2000"
			label="New role"
			placeholder="Select new Rank"
			:options="roles"
			error-visibility="submit"
		>
			<template #decoratorIcon="context">
				<CheckIcon v-if="context.value" class="w-5 h-5 text-white" />
			</template>
		</FormKit>
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
