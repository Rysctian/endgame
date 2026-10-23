<script setup lang="ts">
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import TicketSelect from './ticket-select.vue';
import DueDatePicker from './due-date-picker.vue';
import type { Contract, TicketFields } from './types';
const props = defineProps<{
    contract: Contract;
    errors: Record<string, string>;
    disabled?: boolean;
    currentAssignee?: { id: number; name: string } | null;
}>();
const model = defineModel<TicketFields>({ required: true });
const search = ref('');
const assignee = computed({
    get: () =>
        model.value.assignee_id === null
            ? 'none'
            : String(model.value.assignee_id),
    set: (value: string) =>
        (model.value.assignee_id = value === 'none' ? null : Number(value)),
});
const options = computed(() => {
    const list = [
        { value: 'none', label: 'Unassigned' },
        ...props.contract.assignees,
    ];
    if (
        props.currentAssignee &&
        !list.some((o) => Number(o.value) === props.currentAssignee?.id)
    )
        list.push({
            value: props.currentAssignee.id,
            label: props.currentAssignee.name + ' (current)',
        });
    return list;
});
function searchPeople() {
    router.reload({
        data: { assignee_search: search.value },
        only: ['assignees'],
    });
}
</script>
<template>
    <div class="grid gap-x-4 gap-y-3 sm:grid-cols-2">
        <div>
            <label class="ticket-label">{{ contract.fieldLabels.type }}</label
            ><TicketSelect
                v-model="model.type"
                :options="contract.workTypes"
                :label="contract.fieldLabels.type"
                :disabled="disabled"
            />
            <p v-if="errors.type" class="ticket-error">{{ errors.type }}</p>
        </div>
        <div>
            <label class="ticket-label">{{
                contract.fieldLabels.priority
            }}</label
            ><TicketSelect
                v-model="model.priority"
                :options="contract.priorities"
                :label="contract.fieldLabels.priority"
                :disabled="disabled"
            />
            <p v-if="errors.priority" class="ticket-error">
                {{ errors.priority }}
            </p>
        </div>
        <div>
            <label class="ticket-label">{{
                contract.fieldLabels.assignee_id
            }}</label
            ><TicketSelect
                v-model="assignee"
                :options="options"
                :label="contract.fieldLabels.assignee_id"
                :disabled="disabled"
            />
            <details
                v-if="!disabled"
                class="mt-1 text-xs text-muted-foreground"
            >
                <summary class="cursor-pointer">Find a member</summary>
                <div class="mt-1 flex gap-1">
                    <input
                        v-model="search"
                        aria-label="Search assignees"
                        class="ticket-input min-w-0"
                        placeholder="Search by name"
                        @keydown.enter.prevent="searchPeople"
                    /><button
                        type="button"
                        class="ticket-control"
                        @click="searchPeople"
                    >
                        Search
                    </button>
                </div>
            </details>
            <p v-if="errors.assignee_id" class="ticket-error">
                {{ errors.assignee_id }}
            </p>
        </div>
        <div>
            <label class="ticket-label">{{
                contract.fieldLabels.due_date
            }}</label
            ><DueDatePicker
                v-model="model.due_date"
                :label="contract.fieldLabels.due_date"
                :disabled="disabled"
            />
            <p v-if="errors.due_date" class="ticket-error">
                {{ errors.due_date }}
            </p>
        </div>
    </div>
</template>
