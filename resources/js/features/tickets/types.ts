export interface Option {
    value: string | number;
    label: string;
}
export interface Status {
    id: number;
    code: string;
    name: string;
    category: 'open' | 'active' | 'done';
    position: number;
    is_initial: boolean;
}
export interface Ticket {
    id: number;
    number: number;
    key: string;
    title: string;
    description: string | null;
    type: string;
    priority: string;
    status_id: number;
    sort_order: number;
    version: number;
    assignee_id: number | null;
    assignee: { id: number; name: string } | null;
    due_date: string | null;
    status: Pick<Status, 'id' | 'name' | 'category'>;
}
export interface Activity {
    id: number;
    action: string;
    actor: string | null;
    created_at: string;
    changes: {
        before?: Record<string, unknown>;
        after?: Record<string, unknown>;
    };
}
export interface TicketDetails extends Ticket {
    reporter: { id: number; name: string };
    created_at: string;
    activity: Activity[];
}
export interface Column {
    status_id: number;
    total: number;
    page: number;
    hasMore: boolean;
    next_id: number | null;
    tickets: Ticket[];
}
export interface TicketFields {
    title: string;
    description: string;
    type: string;
    priority: string;
    assignee_id: number | null;
    due_date: string | null;
}
export interface Contract {
    workTypes: Option[];
    priorities: Option[];
    assignees: Option[];
    fieldLabels: Record<keyof TicketFields, string>;
    fieldStates: Record<
        keyof TicketFields,
        { required: boolean; readOnly: boolean }
    >;
    defaults: TicketFields;
    validationRules: {
        title: { maxLength: number };
        description: { maxLength: number };
    };
}
export interface Project {
    id: number;
    name: string;
    key: string;
    status: string;
    board_version: number;
    workflow_version: number;
}
export interface Workspace {
    id: number;
    name: string;
}
export interface Permissions {
    view: boolean;
    create: boolean;
    edit: boolean;
    delete: boolean;
    setup: boolean;
    project: boolean;
}
export interface Transition {
    from_status_id: number;
    to_status_id: number;
}
export interface Filters {
    view: 'board' | 'list' | 'table';
    page?: number;
    search: string;
    status_id?: string;
    priority?: string;
    type?: string;
    assignee_id?: string;
    sort?: string;
    direction?: 'asc' | 'desc';
    ticket?: number;
    column_pages?: Record<number, number>;
}
