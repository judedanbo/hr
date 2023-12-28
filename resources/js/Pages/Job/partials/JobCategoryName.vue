<script setup>
defineProps({ job: { type: Object, required: true } });
</script>
<template>
	<section class="">
		{{ job.category.name }}
		{{ job.category.short_name ? "(" + job.category.short_name + ")" : "" }}
	</section>
</template>
