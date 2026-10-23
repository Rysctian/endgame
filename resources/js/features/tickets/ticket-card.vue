<script setup lang="ts">
import { computed } from 'vue';
import { CalendarDays, SignalHigh, GripVertical } from '@lucide/vue';
import { format, parseISO, isBefore, startOfDay } from 'date-fns';
import type { Ticket, Option } from './types';
const props = defineProps<{
    ticket: Ticket;
    workTypes: Option[];
    priorities: Option[];
    editable: boolean;
}>();
defineEmits<{ open: [ticket: Ticket] }>();
const overdue = computed(
    () =>
        props.ticket.due_date &&
        props.ticket.status.category !== 'done' &&
        isBefore(parseISO(props.ticket.due_date), startOfDay(new Date())),
);
</script>
<template>
    <article
        :data-ticket-id="ticket.id"
        class="group rounded-lg border bg-card p-3 shadow-xs"
        :class="editable ? 'cursor-grab active:cursor-grabbing' : ''"
    >
        <div class="mb-2 flex items-center justify-between">
            <span
                class="rounded border px-1.5 py-0.5 text-[11px] text-muted-foreground"
                >{{
                    workTypes.find((o) => o.value === ticket.type)?.label
                }}</span
            ><GripVertical
                v-if="editable"
                class="size-3.5 text-muted-foreground opacity-0 group-hover:opacity-100"
                aria-hidden="true"
            />
        </div>
        <button
            class="block w-full break-words text-left text-[13px] font-medium leading-5 hover:text-primary focus-visible:rounded focus-visible:ring-2 focus-visible:ring-ring"
            :class="
                ticket.status.category === 'done'
                    ? 'text-muted-foreground line-through'
                    : ''
            "
            @click="$emit('open', ticket)"
        >
            {{ ticket.title }}
        </button>
        <div
            class="mt-3 flex items-center gap-2 text-[11px] text-muted-foreground"
        >
            <span class="font-mono">{{ ticket.key }}</span
            ><span
                :title="
                    priorities.find((o) => o.value === ticket.priority)?.label
                "
                :class="
                    ['high', 'urgent'].includes(ticket.priority)
                        ? 'text-urgency'
                        : ''
                "
                ><SignalHigh class="size-3.5" /><span class="sr-only">{{
                    priorities.find((o) => o.value === ticket.priority)?.label
                }}</span></span
            ><span
                v-if="ticket.due_date"
                class="flex items-center gap-1"
                :class="overdue ? 'text-destructive' : ''"
                ><CalendarDays class="size-3" />{{
                    format(parseISO(ticket.due_date), 'MMM d')
                }}</span
            ><span
                v-if="ticket.assignee"
                class="ticket-avatar ml-auto"
                :title="ticket.assignee.name"
                >{{
                    ticket.assignee.name
                        .split(' ')
                        .map((n) => n[0])
                        .slice(0, 2)
                        .join('')
                }}</span
            >
        </div>
    </article>
</template>
