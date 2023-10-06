<script setup>
import { Inertia } from "@inertiajs/inertia";
import { format, addDays, subYears } from "date-fns";
const emit = defineEmits(["formSubmitted"]);

const props = defineProps({
    person: Number,
    qualification: Object,
});

const submitHandler = (data, node) => {
    Inertia.patch(
        route("qualification.update", {
            qualification: props.qualification.id,
        }),
        data,
        {
            preserveScroll: true,
            onSuccess: () => {
                node.reset();
                emit("formSubmitted");
            },
            onError: (errors) => {
                node.setErrors(["Sever side errors"], errors);
            },
        }
    );
};
</script>

<template>
    <main class="px-8 py-8 bg-gray-100 dark:bg-gray-700">
        <h1 class="text-2xl pb-4 dark:text-gray-100">Edit Qualification</h1>
        <FormKit
            @submit="submitHandler"
            type="form"
            submit-label="Update"
            :value="qualification"
        >
            <!-- <FormKit type="hidden" name="id" id="id" /> -->
            <!-- <FormKit type="hidden" name="person_id" id="person_id" /> -->
            <FormKit
                type="text"
                name="institution"
                id="institution"
                label="Institution"
                validation="string|length:2,100"
                validation-visibility="submit"
            />
            <div class="sm:flex gap-4">
                <FormKit
                    type="text"
                    name="course"
                    id="course"
                    label="Course"
                    validation="required|string|length:2,100"
                    validation-visibility="submit"
                />
                <div>
                    <FormKit
                        type="text"
                        name="level"
                        id="level"
                        label="Level"
                        validation="string|length:2,100"
                        validation-visibility="submit"
                    />
                </div>
            </div>
            <div class="sm:flex gap-4">
                <FormKit
                    type="text"
                    name="qualification"
                    id="qualification"
                    label="Qualification"
                    validation="length:0,100"
                    validation-visibility="submit"
                />
                <div>
                    <FormKit
                        type="text"
                        name="qualification_number"
                        id="qualification_number"
                        label="Qualification Number"
                        validation="string|length:0,100"
                        validation-visibility="submit"
                    />
                </div>
            </div>
            <div class="w-1/2 sm:w-1/3 xl:w-1/4">
                <FormKit
                    type="text"
                    name="year"
                    id="year"
                    label="Year of Graduation"
                    validation="string|length:2,100"
                    validation-visibility="submit"
                />
            </div>
        </FormKit>
    </main>
</template>

<style scoped>
.formkit-outer {
    @apply w-full;
}
.formkit-submit {
    @apply justify-self-end;
}
.formkit-actions {
    @apply flex justify-end;
}
</style>
