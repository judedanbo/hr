<script setup>
import { onMounted, ref } from "vue";
const values = ref([]);

let jobs = ref([]);
onMounted(async () => {
    const response = await axios.get(route("job.create"));
    jobs.value = response.data;
});
</script>
<template>
    <FormKit type="form">
        <FormKit
            v-model="values"
            type="checkbox"
            label="Select Jobs to aff"
            help="select jobs to add to this category"
            name="jobs"
            :options="jobs"
            validation="required"
        />
    </FormKit>
</template>
