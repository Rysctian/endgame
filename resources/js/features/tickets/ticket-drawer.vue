<script setup lang="ts">
import { Textarea } from '@/components/ui/textarea';
import { Input } from '@/components/ui/input';
import { computed, ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import {
    Sheet,
    SheetTrigger,
    SheetContent,
    SheetTitle,
    SheetDescription,
    SheetClose,
} from '@/components/ui/sheet';
import { X, Trash2, Clock3 } from '@lucide/vue';
import { format, parseISO, isBefore, startOfDay } from 'date-fns';
import { Button } from '@/components/ui/button';
import TicketProperties from './ticket-properties.vue';
import TicketSelect from './ticket-select.vue';
import type {
    TicketDetails,
    Contract,
    Status,
    Transition,
    Project,
    Permissions,
} from './types';
const props = defineProps<{
    ticket: TicketDetails | null;
    contract: Contract;
    statuses: Status[];
    transitions: Transition[];
    project: Project;
    can: Permissions;
    url: string;
    busy: boolean;
}>();
const emit = defineEmits<{
    close: [];
    restoreFocus: [];
    move: [ticket: TicketDetails, statusId: number, beforeId: number | null];
}>();
const form = useForm({ ...props.contract.defaults, version: 1 });
const deleting = ref(false);
const confirmDelete = ref(false);
const moveTo = ref('');
function loadTicket() {
    const t = props.ticket;
    if (!t) return;
    form.defaults({
        title: t.title,
        description: t.description ?? '',
        type: t.type,
        priority: t.priority,
        assignee_id: t.assignee_id,
        due_date: t.due_date,
        version: t.version,
    });
    form.reset();
    form.clearErrors();
}
watch(
    () => [props.ticket?.id, props.ticket?.version],
    (next, previous) => {
        if (
            next[0] !== previous?.[0] ||
            (next[1] !== previous?.[1] && !form.isDirty)
        ) {
            loadTicket();
            confirmDelete.value = false;
            moveTo.value = '';
        }
    },
    { immediate: true },
);
const choices = computed(() =>
    props.statuses
        .filter((s) =>
            props.transitions.some(
                (t) =>
                    t.from_status_id === props.ticket?.status_id &&
                    t.to_status_id === s.id,
            ),
        )
        .map((s) => ({ value: s.id, label: s.name })),
);
const overdue = computed(
    () =>
        props.ticket?.due_date &&
        props.ticket.status.category !== 'done' &&
        isBefore(parseISO(props.ticket.due_date), startOfDay(new Date())),
);
function save() {
    if (!props.ticket) return;
    form.patch(props.url + '/' + props.ticket.id, {
        preserveScroll: true,
        onSuccess: () => {
            loadTicket();
            toast.success('Ticket updated.');
        },
        onError: () =>
            toast.error(
                'Unable to update ticket. Check the highlighted fields.',
            ),
    });
}
function transition(value: string) {
    if (props.ticket && value) {
        emit('move', props.ticket, Number(value), null);
        moveTo.value = '';
    }
}
function remove() {
    if (!props.ticket) return;
    deleting.value = true;
    router.delete(props.url + '/' + props.ticket.id, {
        data: { version: props.ticket.version },
        preserveScroll: true,
        onSuccess: () => toast.success('Ticket deleted.'),
        onError: (errors) =>
            toast.error(Object.values(errors)[0] ?? 'Unable to delete ticket.'),
        onFinish: () => (deleting.value = false),
    });
}
function eventLabel(action: string) {
    return (
        (
            {
                'ticket.created': 'created this ticket',
                'ticket.updated': 'updated this ticket',
                'ticket.transitioned': 'changed the status',
                'ticket.reordered': 'changed the position',
                'ticket.deleted': 'deleted this ticket',
            } as Record<string, string>
        )[action] ?? action
    );
}
</script>
<template>
    <Sheet :open="!!ticket" @update:open="!$event && emit('close')"
        ><SheetContent
            :show-close-button="false"
            v-if="ticket"
            class="ticket-drawer gap-0"
            @close-auto-focus="
                $event.preventDefault();
                emit('restoreFocus');
            "
            @interact-outside="
                (form.processing || busy || deleting) && $event.preventDefault()
            "
            ><header
                class="flex shrink-0 items-center gap-2 border-b px-5 py-3"
            >
                <span class="font-mono text-xs text-muted-foreground">{{
                    ticket.key
                }}</span
                ><span class="ml-auto flex items-center gap-2 text-xs"
                    ><span
                        class="status-dot"
                        :data-category="ticket.status.category"
                    />{{ ticket.status.name }}</span
                ><SheetClose
                    class="ticket-icon"
                    aria-label="Close ticket"
                    :disabled="form.processing || busy || deleting"
                    ><X class="size-4"
                /></SheetClose>
            </header>
            <div class="min-h-0 flex-1 overflow-y-auto p-5 sm:p-6">
                <SheetTitle class="sr-only">{{ ticket.key }} details</SheetTitle
                ><SheetDescription class="sr-only"
                    >View and edit this ticket without leaving the
                    project.</SheetDescription
                >
                <div
                    v-if="overdue"
                    class="mb-5 flex items-center gap-2 rounded-md bg-destructive/10 px-3 py-2.5 text-xs text-destructive"
                >
                    <Clock3 class="size-3.5" />Overdue ·
                    {{ ticket.due_date }}
                </div>
                <form class="space-y-5" @submit.prevent="save">
                    <div>
                        <label for="ticket-title" class="sr-only">{{
                            contract.fieldLabels.title
                        }}</label
                        ><Input
                            v-if="can.edit"
                            id="ticket-title"
                            v-model="form.title"
                            class="w-full rounded border border-transparent bg-transparent py-1 text-lg font-semibold hover:border-input focus:border-input focus:outline-none focus:ring-2 focus:ring-ring"
                            :maxlength="
                                contract.validationRules.title.maxLength
                            "
                            required
                        />
                        <h2 v-else class="break-words text-lg font-semibold">
                            {{ ticket.title }}
                        </h2>
                        <p v-if="form.errors.title" class="ticket-error">
                            {{ form.errors.title }}
                        </p>
                    </div>
                    <div
                        class="flex flex-wrap items-center justify-between gap-3 border-y py-3"
                    >
                        <span class="flex items-center gap-2 text-xs"
                            ><span
                                class="status-dot"
                                :data-category="ticket.status.category"
                            />{{ ticket.status.name }}</span
                        ><TicketSelect
                            v-if="can.edit && choices.length"
                            :model-value="moveTo"
                            :options="choices"
                            label="Move to status"
                            placeholder="Move to…"
                            :disabled="busy || form.processing || form.isDirty"
                            @update:model-value="transition"
                        /><span
                            v-if="can.edit && form.isDirty"
                            class="text-xs text-muted-foreground"
                            >Save changes before moving.</span
                        >
                    </div>
                    <TicketProperties
                        v-model="form"
                        :contract="contract"
                        :errors="form.errors"
                        :disabled="!can.edit || form.processing || busy"
                        :current-assignee="ticket.assignee"
                    />
                    <div class="space-y-1 text-xs text-muted-foreground">
                        <p>
                            Reported by
                            <span class="text-foreground">{{
                                ticket.reporter.name
                            }}</span>
                        </p>
                        <p>
                            {{
                                format(
                                    parseISO(ticket.created_at),
                                    'MMM d, yyyy',
                                )
                            }}
                        </p>
                    </div>
                    <div>
                        <label
                            for="ticket-description"
                            class="mb-2 block text-sm font-medium"
                            >{{ contract.fieldLabels.description }}</label
                        ><Textarea
                            v-if="can.edit"
                            id="ticket-description"
                            v-model="form.description"
                            class="ticket-input min-h-36 resize-y"
                            placeholder="Add a description…"
                            :maxlength="
                                contract.validationRules.description.maxLength
                            "
                        />
                        <p
                            v-else
                            class="whitespace-pre-wrap break-words text-sm text-muted-foreground"
                        >
                            {{ ticket.description || 'No description.' }}
                        </p>
                        <p v-if="form.errors.description" class="ticket-error">
                            {{ form.errors.description }}
                        </p>
                    </div>
                    <p v-if="form.errors.version" class="ticket-error">
                        {{ form.errors.version }}
                        <button
                            type="button"
                            class="underline"
                            @click="router.reload()"
                        >
                            Reload ticket
                        </button>
                    </p>
                    <div v-if="can.edit" class="flex justify-end">
                        <Button
                            type="submit"
                            :disabled="form.processing || busy"
                            >{{
                                form.processing ? 'Saving…' : 'Save changes'
                            }}</Button
                        >
                    </div>
                </form>
                <section class="mt-8 border-t pt-5">
                    <h3 class="mb-4 text-sm font-medium">Activity</h3>
                    <ol class="space-y-4">
                        <li
                            v-for="item in ticket.activity"
                            :key="item.id"
                            class="flex gap-3 text-xs"
                        >
                            <span class="ticket-avatar shrink-0">{{
                                (item.actor ?? 'System')
                                    .slice(0, 2)
                                    .toUpperCase()
                            }}</span>
                            <div class="min-w-0">
                                <p>
                                    <span class="font-medium">{{
                                        item.actor ?? 'System'
                                    }}</span>
                                    {{ eventLabel(item.action) }}
                                </p>
                                <p
                                    v-if="item.action === 'ticket.transitioned'"
                                    class="mt-1 text-muted-foreground"
                                >
                                    {{ item.changes.before?.status }} →
                                    {{ item.changes.after?.status }}
                                </p>
                                <p
                                    class="mt-1 text-[11px] text-muted-foreground"
                                >
                                    {{
                                        format(
                                            parseISO(item.created_at),
                                            'MMM d, yyyy · HH:mm',
                                        )
                                    }}
                                </p>
                            </div>
                        </li>
                    </ol>
                    <p
                        v-if="!ticket.activity.length"
                        class="text-xs text-muted-foreground"
                    >
                        No activity yet.
                    </p>
                </section>
                <div v-if="can.delete" class="mt-7 border-t pt-4">
                    <Button
                        v-if="!confirmDelete"
                        variant="ghost"
                        size="sm"
                        class="text-destructive"
                        @click="confirmDelete = true"
                        ><Trash2 class="size-3.5" />Delete ticket</Button
                    >
                    <div
                        v-else
                        class="space-y-3 rounded-md border border-destructive/20 bg-destructive/5 p-3"
                    >
                        <p class="text-sm">
                            Delete {{ ticket.key }}? Its history will be
                            preserved.
                        </p>
                        <div class="flex gap-2">
                            <Button
                                variant="destructive"
                                :disabled="deleting || busy || form.processing"
                                @click="remove"
                                >Confirm delete</Button
                            ><Button
                                variant="outline"
                                :disabled="deleting"
                                @click="confirmDelete = false"
                                >Cancel</Button
                            >
                        </div>
                    </div>
                </div>
            </div></SheetContent
        ></Sheet
    >
</template>
