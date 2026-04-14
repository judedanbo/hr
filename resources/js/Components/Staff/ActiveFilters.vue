<script setup>
import { computed } from 'vue'
import { XMarkIcon } from '@heroicons/vue/20/solid'

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({}),
    },
    filterOptions: {
        type: Object,
        default: () => ({
            jobCategories: [],
            jobs: [],
            units: [],
            departments: [],
            statuses: [],
            genders: [],
        }),
    },
})

const emit = defineEmits(['remove-filter', 'clear-all'])

const activeFilters = computed(() => {
    const filters = []

    // Rank filter
    if (props.filters.rank_id) {
        const rank = props.filterOptions.jobs.find(
            (j) => j.value == props.filters.rank_id
        )
        if (rank) {
            filters.push({
                key: 'rank_id',
                label: 'Rank',
                value: rank.label,
            })
        }
    }

    // Job Category filter
    if (props.filters.job_category_id) {
        const category = props.filterOptions.jobCategories.find(
            (c) => c.value == props.filters.job_category_id
        )
        if (category) {
            filters.push({
                key: 'job_category_id',
                label: 'Category',
                value: category.label,
            })
        }
    }

    // Unit filter
    if (props.filters.unit_id) {
        const unit = props.filterOptions.units.find(
            (u) => u.value == props.filters.unit_id
        )
        if (unit) {
            filters.push({
                key: 'unit_id',
                label: 'Unit',
                value: unit.label,
            })
        }
    }

    // Department filter
    if (props.filters.department_id) {
        const department = props.filterOptions.departments.find(
            (d) => d.value == props.filters.department_id
        )
        if (department) {
            filters.push({
                key: 'department_id',
                label: 'Department',
                value: department.label,
            })
        }
    }

    // Gender filter
    if (props.filters.gender) {
        const gender = props.filterOptions.genders.find(
            (g) => g.value === props.filters.gender
        )
        if (gender) {
            filters.push({
                key: 'gender',
                label: 'Gender',
                value: gender.label,
            })
        }
    }

    // Status filter
    if (props.filters.status) {
        const status = props.filterOptions.statuses.find(
            (s) => s.value === props.filters.status
        )
        if (status) {
            filters.push({
                key: 'status',
                label: 'Status',
                value: status.label,
            })
        }
    }

    // Hire Date Range
    if (props.filters.hire_date_from && props.filters.hire_date_to) {
        filters.push({
            key: 'hire_date',
            label: 'Hired',
            value: `${formatDate(props.filters.hire_date_from)} - ${formatDate(props.filters.hire_date_to)}`,
            removeKeys: ['hire_date_from', 'hire_date_to'],
        })
    } else if (props.filters.hire_date_from) {
        filters.push({
            key: 'hire_date_from',
            label: 'Hired From',
            value: formatDate(props.filters.hire_date_from),
        })
    } else if (props.filters.hire_date_to) {
        filters.push({
            key: 'hire_date_to',
            label: 'Hired To',
            value: formatDate(props.filters.hire_date_to),
        })
    }

    // Age Range
    if (props.filters.age_from && props.filters.age_to) {
        filters.push({
            key: 'age_range',
            label: 'Age',
            value: `${props.filters.age_from} - ${props.filters.age_to} years`,
            removeKeys: ['age_from', 'age_to'],
        })
    } else if (props.filters.age_from) {
        filters.push({
            key: 'age_from',
            label: 'Min Age',
            value: `${props.filters.age_from} years`,
        })
    } else if (props.filters.age_to) {
        filters.push({
            key: 'age_to',
            label: 'Max Age',
            value: `${props.filters.age_to} years`,
        })
    }

    return filters
})

const hasActiveFilters = computed(() => activeFilters.value.length > 0)

const formatDate = (dateString) => {
    if (!dateString) return ''
    const date = new Date(dateString)
    return date.toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    })
}

const removeFilter = (filter) => {
    const keysToRemove = filter.removeKeys || [filter.key]
    emit('remove-filter', keysToRemove)
}

const clearAll = () => {
    emit('clear-all')
}
</script>

<template>
    <div v-if="hasActiveFilters" class="mb-4">
        <div
            class="flex flex-wrap items-center gap-2 rounded-lg bg-indigo-50 dark:bg-indigo-950 px-4 py-3"
        >
            <span
                class="text-sm font-medium text-indigo-900 dark:text-indigo-100"
            >
                Active Filters:
            </span>
            <div class="flex flex-wrap gap-2">
                <span
                    v-for="filter in activeFilters"
                    :key="filter.key"
                    class="inline-flex items-center gap-x-1.5 rounded-full bg-indigo-100 dark:bg-indigo-900 px-3 py-1 text-sm font-medium text-indigo-700 dark:text-indigo-200"
                >
                    <span class="font-semibold">{{ filter.label }}:</span>
                    <span>{{ filter.value }}</span>
                    <button
                        type="button"
                        class="group relative -mr-1 h-4 w-4 rounded-full hover:bg-indigo-600/20"
                        @click="removeFilter(filter)"
                    >
                        <span class="sr-only">Remove {{ filter.label }} filter</span>
                        <XMarkIcon
                            class="h-4 w-4 text-indigo-600 dark:text-indigo-300 group-hover:text-indigo-800 dark:group-hover:text-indigo-100"
                        />
                    </button>
                </span>
            </div>
            <button
                type="button"
                class="ml-auto inline-flex items-center gap-1 rounded-md bg-white dark:bg-gray-800 px-2.5 py-1.5 text-xs font-semibold text-indigo-600 dark:text-indigo-400 shadow-sm ring-1 ring-inset ring-indigo-600/20 dark:ring-indigo-400/20 hover:bg-indigo-50 dark:hover:bg-gray-700"
                @click="clearAll"
            >
                <XMarkIcon class="h-3.5 w-3.5" />
                Clear All
            </button>
        </div>
    </div>
</template>
