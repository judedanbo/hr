<script setup>
import { Inertia } from "@inertiajs/inertia";
import { format, addDays, subYears } from "date-fns";
import QualificationForm from "./partials/QualificationForm.vue";
import QualificationEvidence from "./partials/QualificationEvidence.vue";
const emit = defineEmits(["formSubmitted"]);

const props = defineProps({
    person: Number,
    qualification: Object,
});

const submitDocuments = (document) => {
    console.log(document);
    const formData = new FormData();
    // document.forEach((file) => {
    formData.append("document_file", document[0].file);
    // });
    Inertia.post(
        route("qualification.document.update", {
            qualification: props.qualification.id,
        }),
        formData,
        {
            preserveScroll: true,
            onSuccess: () => {
                // emit("imageUpdated");
            },
            onError: (errors) => {
                // const errorNode = getNode("documentUpload");
                console.log(errors);
                // errorNode.setErrors(errors);
                // errorNode = { errors: "there are errors" }; // TODO fix display server side image errors
            },
        }
    );
};

const submitHandler = (data, node) => {
    // Inertia.patch(
    //     route("qualification.update", {
    //         qualification: props.qualification.id,
    //     }),
    //     data,
    //     {
    //         preserveScroll: true,
    //         onSuccess: () => {
    //             node.reset();
    //             emit("formSubmitted");
    //         },
    //         onError: (errors) => {
    //             node.setErrors(["Sever side errors"], errors);
    //         },
    //     }
    // );
    if (data.staffQualification.evidence.documentUpload.length > 0) {
        submitDocuments(data.staffQualification.evidence.documentUpload);
    }
};
</script>

<template>
    <main class="bg-gray-100 dark:bg-gray-700">
        <h1 class="text-2xl pb-4 dark:text-gray-100">Edit Qualification</h1>
        <FormKit @submit="submitHandler" type="form" :actions="false">
            <!-- <FormKit type="hidden" name="id" id="id" /> -->
            <!-- <FormKit type="hidden" name="person_id" id="person_id" /> -->
            <FormKit
                type="multi-step"
                name="staffQualification"
                :allow-incomplete="true"
                tab-style="progress"
            >
                <FormKit
                    type="step"
                    name="certification"
                    id="certification"
                    :value="qualification"
                >
                    <QualificationForm />
                </FormKit>
                <FormKit
                    type="step"
                    name="evidence"
                    id="evidence"
                    step-actions-class="flex justify-between"
                >
                    <!-- {{ qualification.documents }} -->
                    <QualificationEvidence
                        :documents="qualification.documents"
                    />
                    <template #stepNext>
                        <FormKit type="submit" label="Save" />
                    </template>
                </FormKit>
            </FormKit>
        </FormKit>
    </main>
</template>
