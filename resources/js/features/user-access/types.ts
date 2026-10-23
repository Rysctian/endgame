export interface Account {
    id: number;
    name: string;
    email: string;
    status: 'active' | 'suspended';
    is_system_admin: boolean;
    email_verified_at: string | null;
}
export interface PageLink {
    url: string | null;
    label: string;
    active: boolean;
}
export interface Paginated<T> {
    data: T[];
    links: PageLink[];
    total: number;
}
export interface Workspace {
    id: number;
    name: string;
    slug: string;
}
export interface RoleOption {
    id: number;
    name: string;
}
export interface Role extends RoleOption {
    permissions: string[];
    is_system: boolean;
    code: string;
    canEdit: boolean;
    canDelete: boolean;
}
export interface Member {
    id: number;
    user: Account;
    role: RoleOption;
    canUpdate: boolean;
    canRemove: boolean;
}
export interface Invitation {
    canCancel: boolean;
    id: number;
    email: string;
    role: RoleOption;
    expires_at: string;
}
export interface Activity {
    id: number;
    action: string;
    actor_id: number | null;
    created_at: string;
}
export interface SharedProps {
    auth: { user: Account | null };
    status: string | null;
    errors: Record<string, string>;
    [key: string]: unknown;
}

export interface PermissionModule {
    key: string;
    label: string;
    actions: Record<string, { key: string; label: string; canGrant: boolean }>;
}
