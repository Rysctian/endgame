<script setup lang="ts">
import { computed, ref, watch, nextTick } from 'vue';
import { router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import { Search, Plus, SlidersHorizontal, RotateCcw } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import TicketLayout from '@/layouts/ticket-layout.vue';
import TicketBoard from '@/features/tickets/ticket-board.vue';
import TicketList from '@/features/tickets/ticket-list.vue';
import TicketTable from '@/features/tickets/ticket-table.vue';
import TicketForm from '@/features/tickets/ticket-form.vue';
import TicketDrawer from '@/features/tickets/ticket-drawer.vue';
import TicketSelect from '@/features/tickets/ticket-select.vue';
import PaginationLinks from '@/features/user-access/pagination-links.vue';
import type { Paginated } from '@/features/user-access/types';
import type {
    Contract,
    Project,
    Workspace,
    Ticket,
    TicketDetails,
    Column,
    Status,
    Transition,
    Permissions,
    Filters,
} from '@/features/tickets/types';
const props = defineProps<
    Contract & {
        workspace: Workspace;
        project: Project;
        statuses: Status[];
        transitions: Transition[];
        columns: Column[];
        tickets: Paginated<Ticket> | null;
        selectedTicket: TicketDetails | null;
        filters: Filters;
        can: Permissions;
    }
>();
const base = computed(
    () => '/workspaces/' + props.workspace.id + '/projects/' + props.project.id,
);
const url = computed(() => base.value + '/tickets');
const createOpen = ref(false);
const busy = ref(false);
const search = ref(props.filters.search);
const showFilters = ref(false);
const filterValues = ref({
    priority: props.filters.priority ?? 'all',
    type: props.filters.type ?? 'all',
    status_id: props.filters.status_id
        ? String(props.filters.status_id)
        : 'all',
    assignee_id:
        props.filters.assignee_id !== undefined
            ? String(props.filters.assignee_id)
            : 'all',
});
watch(
    () => props.filters,
    (value) => {
        search.value = value.search;
        filterValues.value = {
            priority: value.priority ?? 'all',
            type: value.type ?? 'all',
            status_id: value.status_id ? String(value.status_id) : 'all',
            assignee_id:
                value.assignee_id !== undefined
                    ? String(value.assignee_id)
                    : 'all',
        };
    },
);
const initial = computed(
    () => props.statuses.find((s) => s.is_initial)?.name ?? '',
);
const activeFilters = computed(
    () =>
        Object.values(filterValues.value).some((v) => v !== 'all') ||
        !!search.value,
);
function navigate(values: Record<string, unknown>) {
    router.get(
        url.value,
        { ...props.filters, ...values },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}
function filter() {
    navigate({
        search: search.value,
        ...Object.fromEntries(
            Object.entries(filterValues.value).map(([k, v]) => [
                k,
                v === 'all' ? null : v,
            ]),
        ),
        page: 1,
        column_pages: null,
        ticket: null,
    });
}
let opener: HTMLElement | null = null;
function restoreFocus() {
    nextTick(() => {
        if (opener?.isConnected) opener.focus();
        else document.getElementById('new-ticket')?.focus();
    });
}
function open(ticket: Ticket) {
    opener = document.activeElement as HTMLElement | null;
    navigate({ ticket: ticket.id });
}
function close() {
    navigate({ ticket: null });
}
function move(ticket: Ticket, statusId: number, beforeId: number | null) {
    if (busy.value) return;
    busy.value = true;
    router.post(
        url.value + '/' + ticket.id + '/move',
        {
            status_id: statusId,
            expected_status_id: ticket.status_id,
            version: ticket.version,
            board_version: props.project.board_version,
            before_id: beforeId,
        },
        {
            preserveScroll: true,
            onSuccess: () => toast.success('Ticket moved.'),
            onError: (errors) => {
                toast.error(
                    Object.values(errors)[0] ?? 'Unable to move ticket.',
                );
                router.reload({
                    only: [
                        'columns',
                        'tickets',
                        'selectedTicket',
                        'project',
                        'statuses',
                        'transitions',
                    ],
                });
            },
            onFinish: () => (busy.value = false),
        },
    );
}
function edit(ticket: Ticket, field: string, value: string | number | null) {
    if (busy.value) return;
    busy.value = true;
    router.patch(
        url.value + '/' + ticket.id,
        {
            title: ticket.title,
            description: ticket.description,
            type: ticket.type,
            priority: ticket.priority,
            assignee_id: ticket.assignee_id,
            due_date: ticket.due_date,
            version: ticket.version,
            [field]: value,
        },
        {
            preserveScroll: true,
            onSuccess: () => toast.success('Ticket updated.'),
            onError: (errors) =>
                toast.error(
                    Object.values(errors)[0] ?? 'Unable to update ticket.',
                ),
            onFinish: () => (busy.value = false),
        },
    );
}
function columnPage(id: number, page: number) {
    navigate({ column_pages: { ...props.filters.column_pages, [id]: page } });
}
</script>
<template>
    <TicketLayout
        :workspace="workspace"
        :project="project"
        :active="filters.view"
        :can-setup="can.setup"
        :can-project="can.project"
        ><div
            class="flex flex-wrap items-center gap-2 border-b px-4 py-2.5 lg:px-6"
        >
            <form class="relative" @submit.prevent="filter">
                <Search
                    class="pointer-events-none absolute left-2.5 top-2 size-3.5 text-muted-foreground"
                /><input
                    v-model="search"
                    type="search"
                    aria-label="Search tickets"
                    placeholder="Search tickets"
                    class="ticket-input h-7 w-48 pl-8 text-xs"
                />
            </form>
            <Button
                variant="ghost"
                size="sm"
                :aria-expanded="showFilters"
                @click="showFilters = !showFilters"
                ><SlidersHorizontal class="size-3.5" />Filter</Button
            ><span class="hidden text-xs text-muted-foreground sm:block">{{
                filters.view === 'table'
                    ? 'Sort: ' + (filters.sort ?? 'number')
                    : 'Sort: Manual'
            }}</span
            ><Button
                v-if="can.create"
                id="new-ticket"
                size="sm"
                class="ml-auto"
                @click="createOpen = true"
                ><Plus class="size-3.5" />New ticket</Button
            >
        </div>
        <form
            v-if="showFilters"
            class="flex flex-wrap items-end gap-3 border-b bg-muted/30 px-4 py-3 lg:px-6"
            @submit.prevent="filter"
        >
            <TicketSelect
                v-model="filterValues.status_id"
                label="Filter status"
                :options="[
                    { value: 'all', label: 'All statuses' },
                    ...statuses.map((s) => ({ value: s.id, label: s.name })),
                ]"
            /><TicketSelect
                v-model="filterValues.priority"
                label="Filter priority"
                :options="[
                    { value: 'all', label: 'All priorities' },
                    ...priorities,
                ]"
            /><TicketSelect
                v-model="filterValues.type"
                label="Filter type"
                :options="[{ value: 'all', label: 'All types' }, ...workTypes]"
            /><TicketSelect
                v-model="filterValues.assignee_id"
                label="Filter assignee"
                :options="[
                    { value: 'all', label: 'All assignees' },
                    { value: '0', label: 'Unassigned' },
                    ...assignees,
                ]"
            /><Button type="submit" size="sm" variant="outline"
                >Apply filters</Button
            ><Button
                v-if="activeFilters"
                type="button"
                variant="ghost"
                size="sm"
                @click="
                    filterValues = {
                        priority: 'all',
                        type: 'all',
                        status_id: 'all',
                        assignee_id: 'all',
                    };
                    search = '';
                    filter();
                "
                ><RotateCcw class="size-3" />Reset</Button
            >
        </form>
        <TicketBoard
            v-if="filters.view === 'board'"
            :columns="columns"
            :statuses="statuses"
            :work-types="workTypes"
            :priorities="priorities"
            :editable="can.edit"
            :can-create="can.create"
            :busy="busy"
            @open="open"
            @create="createOpen = true"
            @move="move"
            @page="columnPage" /><TicketList
            v-else-if="filters.view === 'list'"
            :columns="columns"
            :statuses="statuses"
            :priorities="priorities"
            :editable="can.edit"
            :can-create="can.create"
            :busy="busy"
            @open="open"
            @create="createOpen = true"
            @move="move"
            @page="columnPage" /><template v-else-if="tickets"
            ><TicketTable
                :tickets="tickets.data"
                :contract="props"
                :statuses="statuses"
                :transitions="transitions"
                :editable="can.edit"
                :busy="busy"
                :filters="filters"
                @open="open"
                @edit="edit"
                @move="move"
                @sort="
                    (sort, direction) => navigate({ sort, direction, page: 1 })
                " />
            <div class="flex items-center justify-between gap-4 p-4">
                <span class="text-xs text-muted-foreground"
                    >{{ tickets.total }} tickets</span
                ><PaginationLinks :links="tickets.links" /></div></template
        ><TicketForm
            v-if="can.create"
            v-model:open="createOpen"
            :contract="props"
            :url="url"
            :initial-status="initial" /><TicketDrawer
            :ticket="selectedTicket"
            :contract="props"
            :statuses="statuses"
            :transitions="transitions"
            :project="project"
            :can="can"
            :url="url"
            :busy="busy"
            @close="close"
            @restore-focus="restoreFocus"
            @move="move"
    /></TicketLayout>
</template>
