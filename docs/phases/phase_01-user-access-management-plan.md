# Phase 01 Implementation Plan

> Use executing-plans sequentially. User requires main and will commit personally.

**Goal:** Deliver User & Access Management.
**Architecture:** Native Laravel session/password/notification services; controllers, scoped models, requests and policies; explicit Vue/Inertia forms.
**Spec:** docs/modules/user-access-management.md and docs/SDD.md.

## Constraints
No auth packages, projects/tickets, database resets, branches or commits. Preserve existing data. Review suspended sessions, foreign role IDs, role escalation, accepted links after removal and protected admins.

### Task 1: Accounts
- [x] Write and run failing AccountTest for registration, protected fields, verification, profile/password/reset, login limits and suspended sessions.
- [x] Implement native authentication controllers, middleware, routes and forward migration; run targeted and full tests.

### Task 2: Workspaces
- [x] Write and run failing WorkspaceAccessTest for admin-only workspace/account changes, isolation, role escalation and audit.
- [x] Implement scoped models, WorkspacePolicy, requests and controllers. Permission boundary: User::hasWorkspacePermission(Workspace,string): bool. Lock workspace rows during coordinated mutations. Run tests.

### Task 3: Invitations
- [x] Write and run failing InvitationTest for email, hash, expiry, cancellation, scoped roles and idempotency.
- [x] Add notification/controller and InvitationAcceptanceService::accept(WorkspaceInvitation,User,string): void. Run tests.

### Task 4: UI and verification
- [x] Write and run Playwright auth and invitation/member workflows against isolated test database.
- [x] Build typed auth/profile/workspace/admin pages and feature dialogs; add factories, repeatable demo seed and explicit admin provisioning command.
- [x] Run PHPUnit, Pint, vue-tsc, Vitest, Vite build and Playwright; review security and reconcile blueprint.



## Task 5: Custom Roles and Module Permissions

### Permission Structure

- [x] Define module permissions in Laravel configuration using `view`, `create`, `edit`, `delete`, and `setup` where applicable.
- [x] Support custom roles with `name`, unique workspace-scoped `code`, and JSON permission mapping.
- [x] Provide role management UI with a module-permission checkbox matrix.
- [x] Support creating, editing, deleting, and assigning custom roles.
- [x] Use existing workspace membership and `User::hasWorkspacePermission()` for authorization.

### Access Rules

- [x] **System Admin:** Full system access and ownership; protected from normal role assignment.
- [x] **Manager:** Manage workspace members, roles, and authorized module permissions.
- [x] **Member:** Access only permitted modules and records.
- [x] Protect System Admin as a global identity and the default Manager/Member workspace roles from deletion.
- [x] Prevent Managers from granting permissions beyond their own authority or assigning System Admin privileges.
- [x] Validate permission keys, role assignments, workspace scope, and protected fields on the backend.
- [x] Apply role changes immediately to subsequent authorized requests.
- [x] Record role and permission changes in activity logs.

### Database and Integration

- [x] Reuse existing `roles` and `workspace_members` tables; add missing fields through safe migrations only when needed.
- [x] Store permission keys as JSON, e.g. `projects.view`, `projects.create`, `projects.edit`.
- [x] Keep permission definitions centralized; avoid separate permission tables or packages.
- [x] Preserve existing roles, memberships, and access during migration.
- [x] Define permissions for existing modules. Future modules register their permissions when implemented.

### Testing and Documentation

- [x] Test role CRUD, permission enforcement, unauthorized access, privilege escalation, and workspace isolation.
- [x] Test custom role assignment and permission changes through Playwright.
- [x] Run affected PHPUnit, Pint, type checks, build, and Playwright tests.
- [x] Update `docs/modules/user-access-management.md` as the complete, current module blueprint, including validations, permissions, database structure, and Mermaid flowcharts.
- [x] Reconcile `docs/SDD.md` if system-level design changes.
