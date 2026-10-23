<script setup lang="ts">
import { ref } from 'vue';
import {
    ChevronDown,
    ChevronRight,
    Plus,
    ArrowUp,
    ArrowDown,
} from '@lucide/vue';
import type { Column, Status, Ticket, Option } from './types';
const props = defineProps<{
    columns: Column[];
    statuses: Status[];
    priorities: Option[];
    canCreate: boolean;
    editable: boolean;
    busy: boolean;
}>();
const emit = defineEmits<{
    open: [ticket: Ticket];
    create: [];
    move: [ticket: Ticket, statusId: number, beforeId: number | null];
    page: [statusId: number, page: number];
}>();
const collapsed = ref<number[]>([]);
function toggle(id: number) {
    collapsed.value = collapsed.value.includes(id)
        ? collapsed.value.filter((x) => x !== id)
        : [...collapsed.value, id];
}
function reorder(column: Column, index: number, direction: number) {
    const ticket = column.tickets[index];
    const anchor =
        direction < 0
            ? column.tickets[index - 1]?.id
            : (column.tickets[index + 2]?.id ?? column.next_id);
    emit('move', ticket, column.status_id, anchor ?? null);
}
</script>
<template>
    <div class="overflow-x-auto px-4 lg:px-6">
        <div class="min-w-[720px]">
            <div
                class="grid grid-cols-[minmax(260px,1fr)_150px_100px_100px_80px] gap-4 border-b py-3 text-xs text-muted-foreground"
            >
                <span>Ticket</span><span>Assignee</span><span>Priority</span
                ><span>Due date</span><span class="sr-only">Ordering</span>
            </div>
            <section v-for="column in columns" :key="column.status_id">
                <div class="flex items-center gap-2 border-b py-3">
                    <button
                        class="ticket-icon"
                        :aria-label="
                            'Toggle ' +
                            statuses.find((s) => s.id === column.status_id)
                                ?.name
                        "
                        :aria-expanded="!collapsed.includes(column.status_id)"
                        @click="toggle(column.status_id)"
                    >
                        <component
                            :is="
                                collapsed.includes(column.status_id)
                                    ? ChevronRight
                                    : ChevronDown
                            "
                            class="size-3.5"
                        /></button
                    ><span
                        class="status-dot"
                        :data-category="
                            statuses.find((s) => s.id === column.status_id)
                                ?.category
                        "
                    />
                    <h2 class="text-xs font-semibold">
                        {{
                            statuses.find((s) => s.id === column.status_id)
                                ?.name
                        }}
                    </h2>
                    <span class="text-xs text-muted-foreground">{{
                        column.total
                    }}</span>
                </div>
                <template v-if="!collapsed.includes(column.status_id)"
                    ><div
                        v-for="(ticket, index) in column.tickets"
                        :key="ticket.id"
                        class="grid grid-cols-[minmax(260px,1fr)_150px_100px_100px_80px] items-center gap-4 border-b py-2.5 text-xs hover:bg-muted/40"
                    >
                        <div class="flex min-w-0 items-center gap-3 pl-8">
                            <span
                                class="shrink-0 font-mono text-[11px] text-muted-foreground"
                                >{{ ticket.key }}</span
                            ><button
                                class="truncate text-left hover:text-primary"
                                @click="emit('open', ticket)"
                            >
                                {{ ticket.title }}
                            </button>
                        </div>
                        <span class="truncate text-muted-foreground">{{
                            ticket.assignee?.name ?? 'Unassigned'
                        }}</span
                        ><span>{{
                            priorities.find((o) => o.value === ticket.priority)
                                ?.label
                        }}</span
                        ><span class="text-muted-foreground">{{
                            ticket.due_date ?? '—'
                        }}</span>
                        <div v-if="editable" class="flex">
                            <button
                                class="ticket-icon"
                                :disabled="busy || index === 0"
                                :aria-label="'Move ' + ticket.key + ' up'"
                                @click="reorder(column, index, -1)"
                            >
                                <ArrowUp class="size-3.5" /></button
                            ><button
                                class="ticket-icon"
                                :disabled="
                                    busy || index === column.tickets.length - 1
                                "
                                :aria-label="'Move ' + ticket.key + ' down'"
                                @click="reorder(column, index, 1)"
                            >
                                <ArrowDown class="size-3.5" />
                            </button>
                        </div>
                    </div>
                    <button
                        v-if="
                            canCreate &&
                            statuses.find((s) => s.id === column.status_id)
                                ?.is_initial
                        "
                        class="flex items-center gap-2 px-3 py-3 text-xs text-muted-foreground hover:text-foreground"
                        @click="emit('create')"
                    >
                        <Plus class="size-3.5" />Add ticket
                    </button>
                    <p
                        v-else-if="!column.tickets.length"
                        class="p-3 pl-8 text-xs text-muted-foreground"
                    >
                        No tickets
                    </p>
                    <div
                        v-if="column.page > 1 || column.hasMore"
                        class="flex gap-3 py-2 text-xs"
                    >
                        <button
                            :disabled="column.page === 1"
                            @click="
                                emit('page', column.status_id, column.page - 1)
                            "
                        >
                            Previous</button
                        ><span>Page {{ column.page }}</span
                        ><button
                            :disabled="!column.hasMore"
                            @click="
                                emit('page', column.status_id, column.page + 1)
                            "
                        >
                            Next
                        </button>
                    </div></template
                >
            </section>
        </div>
    </div>
</template>
