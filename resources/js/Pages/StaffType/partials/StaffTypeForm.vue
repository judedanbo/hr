<script setup>
import { ref, onMounted } from "vue";
import { format, addDays, subYears, addYears } from "date-fns";

const today = format(new Date(), "yyyy-MM-dd");
const start_date = format(addDays(new Date(), 1), "yyyy-MM-dd");
const end_date = format(addYears(new Date(), 3), "yyyy-MM-dd");

const props = defineProps({
    institution: Number,
    hire_date: String,
});
let types = ref([]);

onMounted(async () => {
    const response = await axios.get(
        route("institution.staff-types", { institution: props.institution })
    );
    types.value = response.data;
});
</script>
<template>
    <FormKit
        type="hidden"
        id="hire_date"
        name="hire_date"
        :value="props.hire_date"
    />
    <FormKit
        type="select"
        name="staff_type"
        id="staff_type"
        validation="required|string"
        label="Staff type"
        placeholder="Select staff type"
        :options="types"
        error-visibility="submit"
    />
    <div class="sm:flex gap-4">
        <FormKit
            type="date"
            name="start_date"
            :max="today"
            id="start_date"
            label="Start date"
            :validation="'required|date_before:' + start_date"
            validation-visibility="submit"
            outer-class="sm:flex-1"
        />
        <FormKit
            type="date"
            name="end_date"
            id="end_date"
            :max="end_date"
            label="End date"
            :validation="'date_before:' + end_date"
            validation-visibility="submit"
            outer-class="sm:flex-1"
        />
    </div>
</template>
