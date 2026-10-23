<script setup lang="ts">
import { ref, watch } from 'vue';
import { VueDraggable, type DraggableEvent } from 'vue-draggable-plus';
import { Plus, ChevronLeft, ChevronRight } from '@lucide/vue';
import TicketCard from './ticket-card.vue';
import type { Column, Status, Ticket, Option } from './types';
const props = defineProps<{
    columns: Column[];
    statuses: Status[];
    workTypes: Option[];
    priorities: Option[];
    editable: boolean;
    canCreate: boolean;
    busy: boolean;
}>();
const emit = defineEmits<{
    open: [ticket: Ticket];
    create: [];
    move: [ticket: Ticket, statusId: number, beforeId: number | null];
    page: [statusId: number, page: number];
}>();
const local = ref<Column[]>([]);
const before = ref<Column[]>([]);
const dragging = ref(false);
const reduced = ref(
    globalThis.matchMedia?.('(prefers-reduced-motion: reduce)').matches ??
        false,
);
watch(
    () => props.columns,
    (value) => {
        if (!dragging.value)
            local.value = value.map((c) => ({ ...c, tickets: [...c.tickets] }));
    },
    { immediate: true, deep: true },
);
watch(
    () => props.busy,
    (value) => {
        if (!value && !dragging.value)
            local.value = props.columns.map((c) => ({
                ...c,
                tickets: [...c.tickets],
            }));
    },
);
function start() {
    dragging.value = true;
    before.value = props.columns.map((c) => ({
        ...c,
        tickets: [...c.tickets],
    }));
}
function end(event: DraggableEvent) {
    dragging.value = false;
    const id = Number(event.item.dataset.ticketId);
    const ticket = before.value
        .flatMap((c) => c.tickets)
        .find((t) => t.id === id);
    const targetElement = event.to as HTMLElement;
    const target = Number(targetElement.dataset.statusId);
    const ids = Array.from(
        targetElement.querySelectorAll<HTMLElement>('[data-ticket-id]'),
    ).map((el) => Number(el.dataset.ticketId));
    const index = ids.indexOf(id);
    if (
        ticket &&
        index !== -1 &&
        (event.from !== event.to || event.oldIndex !== event.newIndex)
    )
        emit(
            'move',
            ticket,
            target,
            ids[index + 1] ??
                props.columns.find((c) => c.status_id === target)?.next_id ??
                null,
        );
    else
        local.value = props.columns.map((c) => ({
            ...c,
            tickets: [...c.tickets],
        }));
}
</script>
<template>
    <div
        class="flex items-start gap-3 overflow-x-auto px-4 py-4 lg:px-6"
        aria-label="Ticket board"
    >
        <section
            v-for="column in local"
            :key="column.status_id"
            :data-column-code="
                statuses.find((s) => s.id === column.status_id)?.code
            "
            class="w-[282px] shrink-0 rounded-xl border border-border/60 bg-board p-2"
        >
            <header class="flex items-center gap-2 px-1 pb-3 pt-1">
                <span
                    class="status-dot"
                    :data-category="
                        statuses.find((s) => s.id === column.status_id)
                            ?.category
                    "
                />
                <h2 class="min-w-0 truncate text-xs font-semibold">
                    {{ statuses.find((s) => s.id === column.status_id)?.name }}
                </h2>
                <span class="text-xs text-muted-foreground">{{
                    column.total
                }}</span
                ><button
                    v-if="
                        canCreate &&
                        statuses.find((s) => s.id === column.status_id)
                            ?.is_initial
                    "
                    class="ticket-icon ml-auto"
                    aria-label="Add ticket"
                    @click="emit('create')"
                >
                    <Plus class="size-3.5" />
                </button>
            </header>
            <VueDraggable
                v-model="column.tickets"
                data-dropzone
                :data-status-id="column.status_id"
                group="tickets"
                :animation="reduced ? 0 : 180"
                easing="cubic-bezier(0.23, 1, 0.32, 1)"
                :disabled="!editable || busy"
                :force-fallback="true"
                :fallback-on-body="true"
                :fallback-tolerance="5"
                :delay-on-touch-only="true"
                :delay="180"
                ghost-class="ticket-drag-ghost"
                chosen-class="ticket-drag-chosen"
                class="min-h-16 space-y-2"
                @start="start"
                @end="end"
                ><TicketCard
                    v-for="ticket in column.tickets"
                    :key="ticket.id"
                    :ticket="ticket"
                    :work-types="workTypes"
                    :priorities="priorities"
                    :editable="editable && !busy"
                    @open="emit('open', $event)"
            /></VueDraggable>
            <p
                v-if="!column.tickets.length"
                class="px-2 pb-3 text-xs text-muted-foreground"
            >
                No tickets
            </p>
            <button
                v-if="
                    canCreate &&
                    statuses.find((s) => s.id === column.status_id)?.is_initial
                "
                class="mt-1 flex w-full items-center gap-2 rounded px-2 py-2 text-xs text-muted-foreground hover:bg-accent hover:text-foreground"
                @click="emit('create')"
            >
                <Plus class="size-3.5" />Add ticket
            </button>
            <div
                v-if="column.page > 1 || column.hasMore"
                class="mt-2 flex items-center justify-between border-t pt-2 text-xs text-muted-foreground"
            >
                <button
                    :disabled="column.page === 1 || busy"
                    class="ticket-icon"
                    aria-label="Previous column page"
                    @click="emit('page', column.status_id, column.page - 1)"
                >
                    <ChevronLeft class="size-3.5" /></button
                ><span>Page {{ column.page }}</span
                ><button
                    :disabled="!column.hasMore || busy"
                    class="ticket-icon"
                    aria-label="Next column page"
                    @click="emit('page', column.status_id, column.page + 1)"
                >
                    <ChevronRight class="size-3.5" />
                </button>
            </div>
        </section>
    </div>
</template>
