<script setup>
import { computed } from "vue";
import { format, differenceInYears, addYears } from "date-fns";
import StaffTableData from "./StaffTableData.vue";

let props = defineProps({
    staff: Object,
});
let full_name = computed(() => {
    return `${props.staff.title ?? ""} ${props.staff.other_names} ${
        props.staff.first_name ?? ""
    } ${props.staff.surname}`;
});

let dob = computed(() => {
    return formatDate(props.staff.date_of_birth);
});
let hireDate = computed(() => {
    return formatDate(props.staff.hire_date);
});

let formatDate = (dateString) => {
    const date = new Date(dateString);
    return format(date, "dd MMMM, yyyy");
};

let getAge = (dateString, now = new Date()) => {
    const date = new Date(dateString);

    return differenceInYears(now, date);
};
</script>
<template>
    <tr
        :class="staff.status == 'Retired' ? 'bg-gray-300' : ''"
        tabindex="0"
        class="focus:outline-none h-10 border border-gray-100 rounded hover:bg-green-50"
    >
        <StaffTableData>
            <div class="flex flex-col ml-2 text-gray-600 py-2 space-y-1">
                <p class="leading-none">
                    {{ full_name }}
                </p>
                <p
                    class="text-xs"
                    :title="getAge(staff.date_of_birth) + ' years old'"
                >
                    {{ staff.gender }} | {{ dob }}
                </p>
            </div>
        </StaffTableData>
        <StaffTableData>
            <div class="flex flex-col text-gray-600 ml-2 py-2 space-y-1">
                <p class="leading-none">
                    {{ staff.staff_number }}
                </p>
                <p class="text-sm leading-none">
                    {{ staff.old_staff_number }}
                </p>
            </div>
        </StaffTableData>

        <StaffTableData>
            <div class="flex flex-col text-gray-600 ml-2 py-2 space-y-1">
                <p class="leading-none">
                    {{ hireDate }}
                </p>
                <p class="text-sm">
                    {{ staff.years_employed }}
                    years employed
                </p>
            </div>
        </StaffTableData>

        <StaffTableData>
            <div class="flex flex-col ml-2 py-2 space-y-1">
                <p class="leading-none text-gray-600">
                    {{ staff.current_job.name }}
                </p>
                <p class="text-sm">
                    {{ getAge(staff.current_job.start_date) }} years
                </p>
            </div>
        </StaffTableData>
    </tr>
</template>
