<script setup>
defineProps(["message"]);
</script>

<template>
	<div v-show="message">
		<p class="text-sm text-red-600 dark:text-rose-200">
			{{ message }}
		</p>
	</div>
</template>
