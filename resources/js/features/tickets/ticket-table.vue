<script setup lang="ts">
import { computed } from 'vue';
import {
    createColumnHelper,
    FlexRender,
    getCoreRowModel,
    useVueTable,
    type SortingState,
} from '@tanstack/vue-table';
import { ArrowUp, ArrowDown } from '@lucide/vue';
import TicketSelect from './ticket-select.vue';
import DueDatePicker from './due-date-picker.vue';
import type { Ticket, Contract, Status, Transition, Filters } from './types';
const props = defineProps<{
    tickets: Ticket[];
    contract: Contract;
    statuses: Status[];
    transitions: Transition[];
    editable: boolean;
    busy: boolean;
    filters: Filters;
}>();
const emit = defineEmits<{
    open: [ticket: Ticket];
    edit: [ticket: Ticket, field: string, value: string | number | null];
    move: [ticket: Ticket, statusId: number, beforeId: number | null];
    sort: [field: string, direction: 'asc' | 'desc'];
}>();
const helper = createColumnHelper<Ticket>();
const columns = [
    helper.accessor('title', { header: 'Ticket', enableSorting: true }),
    helper.accessor('status_id', { header: 'Status', enableSorting: false }),
    helper.accessor('priority', { header: 'Priority', enableSorting: false }),
    helper.accessor('assignee_id', {
        header: 'Assignee',
        enableSorting: false,
    }),
    helper.accessor('due_date', { header: 'Due date', enableSorting: true }),
    helper.accessor('type', { header: 'Type', enableSorting: false }),
    helper.accessor('number', { header: 'Key', enableSorting: true }),
];
const sorting = computed<SortingState>(() => [
    {
        id: props.filters.sort ?? 'number',
        desc: props.filters.direction === 'desc',
    },
]);
const table = useVueTable({
    get data() {
        return props.tickets;
    },
    columns,
    getCoreRowModel: getCoreRowModel(),
    manualSorting: true,
    state: {
        get sorting() {
            return sorting.value;
        },
    },
    onSortingChange: (updater) => {
        const next =
            typeof updater === 'function' ? updater(sorting.value) : updater;
        emit('sort', next[0]?.id ?? 'number', next[0]?.desc ? 'desc' : 'asc');
    },
});
function statusOptions(ticket: Ticket) {
    return props.statuses
        .filter(
            (s) =>
                s.id === ticket.status_id ||
                props.transitions.some(
                    (t) =>
                        t.from_status_id === ticket.status_id &&
                        t.to_status_id === s.id,
                ),
        )
        .map((s) => ({ value: s.id, label: s.name }));
}
function assigneeOptions(ticket: Ticket) {
    const list = [
        { value: 'none', label: 'Unassigned' },
        ...props.contract.assignees,
    ];
    if (
        ticket.assignee &&
        !list.some((o) => Number(o.value) === ticket.assignee_id)
    )
        list.push({
            value: ticket.assignee.id,
            label: ticket.assignee.name + ' (current)',
        });
    return list;
}
</script>
<template>
    <div class="overflow-x-auto">
        <table
            class="ticket-data-table w-full min-w-[1050px] border-collapse text-xs"
        >
            <thead>
                <tr v-for="group in table.getHeaderGroups()" :key="group.id">
                    <th
                        v-for="header in group.headers"
                        :key="header.id"
                        class="border-b border-r px-4 py-3 text-left font-normal text-muted-foreground"
                    >
                        <button
                            v-if="header.column.getCanSort()"
                            class="flex items-center gap-2"
                            @click="header.column.toggleSorting()"
                        >
                            <FlexRender
                                :render="header.column.columnDef.header"
                                :props="header.getContext()"
                            /><component
                                :is="
                                    header.column.getIsSorted() === 'desc'
                                        ? ArrowDown
                                        : ArrowUp
                                "
                                v-if="header.column.getIsSorted()"
                                class="size-3"
                            /></button
                        ><FlexRender
                            v-else
                            :render="header.column.columnDef.header"
                            :props="header.getContext()"
                        />
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="row in table.getRowModel().rows"
                    :key="row.id"
                    class="hover:bg-muted/40"
                >
                    <td
                        v-for="cell in row.getVisibleCells()"
                        :key="cell.id"
                        class="border-b border-r px-3 py-1.5"
                    >
                        <button
                            v-if="cell.column.id === 'title'"
                            class="w-full min-w-56 max-w-md truncate text-left font-medium hover:text-primary"
                            @click="emit('open', row.original)"
                        >
                            {{ row.original.title }}</button
                        ><template v-else-if="cell.column.id === 'status_id'"
                            ><TicketSelect
                                v-if="editable"
                                :model-value="String(row.original.status_id)"
                                :options="statusOptions(row.original)"
                                :label="'Status for ' + row.original.key"
                                :disabled="busy"
                                @update:model-value="
                                    Number($event) !== row.original.status_id &&
                                    emit(
                                        'move',
                                        row.original,
                                        Number($event),
                                        null,
                                    )
                                "
                            /><span v-else>{{
                                row.original.status.name
                            }}</span></template
                        ><template v-else-if="cell.column.id === 'priority'"
                            ><TicketSelect
                                v-if="editable"
                                :model-value="row.original.priority"
                                :options="contract.priorities"
                                :label="'Priority for ' + row.original.key"
                                :disabled="busy"
                                @update:model-value="
                                    emit(
                                        'edit',
                                        row.original,
                                        'priority',
                                        $event,
                                    )
                                "
                            /><span v-else>{{
                                contract.priorities.find(
                                    (o) => o.value === row.original.priority,
                                )?.label
                            }}</span></template
                        ><template v-else-if="cell.column.id === 'assignee_id'"
                            ><TicketSelect
                                v-if="editable"
                                :model-value="
                                    row.original.assignee_id === null
                                        ? 'none'
                                        : String(row.original.assignee_id)
                                "
                                :options="assigneeOptions(row.original)"
                                :label="'Assignee for ' + row.original.key"
                                :disabled="busy"
                                @update:model-value="
                                    emit(
                                        'edit',
                                        row.original,
                                        'assignee_id',
                                        $event === 'none'
                                            ? null
                                            : Number($event),
                                    )
                                "
                            /><span v-else>{{
                                row.original.assignee?.name ?? 'Unassigned'
                            }}</span></template
                        ><template v-else-if="cell.column.id === 'due_date'"
                            ><DueDatePicker
                                v-if="editable"
                                :model-value="row.original.due_date"
                                :label="'Due date for ' + row.original.key"
                                :disabled="busy"
                                @update:model-value="
                                    emit(
                                        'edit',
                                        row.original,
                                        'due_date',
                                        $event,
                                    )
                                "
                            /><span v-else>{{
                                row.original.due_date ?? '—'
                            }}</span></template
                        ><template v-else-if="cell.column.id === 'type'">{{
                            contract.workTypes.find(
                                (o) => o.value === row.original.type,
                            )?.label
                        }}</template
                        ><span v-else class="font-mono text-muted-foreground">{{
                            row.original.key
                        }}</span>
                    </td>
                </tr>
            </tbody>
        </table>
        <p
            v-if="!tickets.length"
            class="p-8 text-center text-sm text-muted-foreground"
        >
            No tickets match your filters.
        </p>
    </div>
</template>
