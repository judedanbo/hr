<template>
    <FormKit
        type="date"
        name="hire_date"
        id="hire_date"
        label="Date of Employment"
        validation="required|date|before_or_equal:today"
        validation-visibility="submit"
    />

    <FormKit
        type="text"
        name="file_number"
        id="file_number"
        label="File number"
        placeholder="File number"
        validation="required|length:2,10"
    />

    <FormKit
        type="text"
        name="staff_number"
        id="staff_number"
        label="Staff number"
        placeholder="Staff number"
        validation="required|length:2,10"
    />

    <!-- <FormKit
        type="textarea"
        name="remarks"
        id="remarks"
        label="Remarks"
        placeholder="Remarks"
        validation="length:2,200"
    /> -->
</template>
