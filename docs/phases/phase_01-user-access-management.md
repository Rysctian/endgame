# Module 01 — User & Access Management

The complete module blueprint and implemented behavior are maintained in [User & Access Management](../modules/user-access-management.md).

## Goal
Manage user accounts, workspace membership, roles, and permissions for a Jira-like task manager. Keep implementation simple and use native Laravel features.

## Default Roles
| Role | Scope | Access |
|---|---|---|
| System Admin | Global | Full system access and ownership; manages all workspaces, users, settings, and records. |
| Manager | Workspace | Manages members, workspace roles/permissions, projects, and tickets. Cannot grant or modify System Admin access. |
| Member | Permitted projects | Views and works on authorized projects/tickets; no workspace administration. |

- System Admin is a **global user flag** (`users.is_system_admin`), not an assignable workspace role.
- Seed **Manager** and **Member** as default workspace roles. No default Viewer or separate Owner role.
- Allow custom workspace roles using explicit permission keys; never allow privilege escalation beyond the actor's permissions.

## Core Processes
1. **Account:** Register/sign in, sign out, edit profile, change/reset password, verify email, and suspend/reactivate accounts (admin only).
2. **Workspace:** System Admin creates/manages workspaces; users can belong to multiple workspaces with different roles.
3. **Invitation:** Manager/System Admin invites an email with a workspace role. Recipient verifies email, signs in/registers, and accepts the invitation.
4. **Membership:** On acceptance, create membership transactionally. Managers can list members, change their workspace roles, and remove them; never delete the underlying user account.
5. **Access checks:** Authenticate → verify active account → check workspace membership and role permissions → apply project/ticket policy. System Admin bypasses workspace restrictions.
6. **Audit:** Record invitations, membership/role changes, account administration, and other security-relevant actions.

## Permission Model
- Permission keys use `resource.action`, e.g. `members.view`, `members.create`, `members.edit`, `members.delete`, `roles.setup`, `activity.view`.
- Store allowed keys in `roles.permissions` (JSON). Define the supported keys in Laravel code/config.
- Laravel **Policies/Gates** enforce both permissions and resource scope. Frontend visibility is UX only, never security.
- Manager permissions apply within their workspace. Members can only access projects they are permitted to join (project membership defined in Project module).
- Protect System Admin status and prevent Managers from granting system-wide access.

## Minimal Tables
| Table | Key fields |
|---|---|
| `users` | id, name, email (unique), password, status, email_verified_at, is_system_admin |
| `workspaces` | id, name, slug (unique) |
| `roles` | id, workspace_id, name, code (unique within workspace), permissions (JSON), is_system |
| `workspace_members` | id, workspace_id, user_id, role_id; unique(workspace_id, user_id) |
| `workspace_invitations` | id, workspace_id, email, role_id, token_hash, expires_at, accepted_at, invited_by |
| `activity_logs` | id, workspace_id (nullable), actor_id, action, subject_type, subject_id, changes, created_at |

`project_members` and project-specific policy rules belong to the Project Management module.

## Implementation Rules
- Laravel session guard, Hash, Form Requests, password broker, Notifications, Policies/Gates, and RateLimiter. **No Fortify, Sanctum, Jetstream, or Spatie Permission.**
- Vue 3 + Inertia forms; conventional controllers; focused service for invitation acceptance and multi-record account operations only.
- Use database transactions for invitation acceptance and coordinated membership/role changes.
- Hash invitation tokens; enforce expiration, email matching, single use, and idempotent acceptance.
- Regenerate session on login; invalidate session on logout; protect against CSRF and brute force.
- Enforce tenant scoping on every workspace query; do not trust workspace IDs from the client.

## Done When
- Users can authenticate and manage their accounts.
- Admin can manage workspaces and users globally.
- Managers can invite/manage workspace members and roles without escalating privileges.
- Members only see authorized workspace/project resources.
- Invitations, permission checks, and activity logging have automated tests.
