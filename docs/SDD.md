# Endgame — Software Design Document (SDD)

**Product:** Jira-inspired task management application  
**Status:** Planned design; not an implementation report  
**Approach:** Modular monolith, maintainable code, minimal dependencies

## 1. Goal & Scope

Manage workspace members, projects, tickets, assignments, and work progress in one application. Prioritize the core ticketing workflow before advanced agile features or integrations.

**Core flow:** User account → Workspace membership → Project access → Ticket/backlog → Assignment → Board/status changes → Review → Done → Activity history.

### MVP Modules
1. **User & Access Management:** Accounts, workspaces, invitations, default/custom roles, permissions, account administration.
2. **Project Management:** Projects, project keys, membership, settings, project-scoped access.
3. **Ticket, Kanban & Workflow Management (P03):** Ticket creation/editing, types, priority, assignee, due date, configurable project statuses and transitions, board ordering, list/table views, and scoped activity history.

**Later:** Comments, attachments, notifications, sprints, epics, subtasks/parent relationships, estimates, advanced reports, real-time collaboration, integrations, and agentic automation. Do not implement these unless separately authorized.

## 2. Tech Stack

| Area | Choice |
|---|---|
| Architecture | One Laravel modular monolith |
| Backend | Laravel 13, PHP 8.4+, Eloquent ORM |
| Frontend | Vue 3 Composition API, TypeScript, Inertia.js 3 |
| UI | shadcn-vue, Tailwind CSS 4, Lucide Vue |
| Data | MySQL 8.4 LTS |
| Tooling | Vite, pnpm, Composer |
| Board / tables | vue-draggable-plus, TanStack Vue Table |
| Utilities | date-fns, Vue Sonner |
| Testing | PHPUnit or Pest; Playwright for end-to-end; Vitest where useful |

Use Laravel's built-in **session guard, hashing, validation, password broker, notifications, policies/gates, and rate limiter**. Build conventional controllers and explicit Inertia forms. Use focused services only when an operation coordinates multiple steps or records. **No Fortify, Sanctum, Jetstream, Spatie Permission, or separate frontend API.** Start with a clean Laravel app and integrate Vue/Inertia manually.

Start with built-in database queues if needed. Add Redis/Horizon, Reverb/Echo, Docker, CI/CD, and AWS deployment infrastructure only as requirements justify them.

## 3. Users, Roles & Permissions

| Default role | Scope | Responsibilities |
|---|---|---|
| **System Admin** | Global | Full system access and ownership; manage all users and workspaces. |
| **Manager** | Workspace | Manage workspace members, roles, projects, and tickets. |
| **Member** | Permitted projects | View and work on authorized projects and tickets only. |

- System Admin is a protected **global flag on `users`**, not a workspace role.
- Seed only **Manager** and **Member** as default workspace roles. No default Viewer or separate Owner role.
- System Admin and Managers can create, edit, assign, and delete **custom workspace roles** within their authority. Managers cannot grant permissions they do not have or modify System Admin status.
- Define module permissions centrally in Laravel configuration using `resource.action` keys and supported `view`, `create`, `edit`, `delete`, and `setup` actions. Store enabled keys as a JSON array on roles. Phase 01 registers workspace members, roles/permissions, and activity; Phase 02 registers projects; Phase 03 registers tickets (including workflow setup). Future modules register actions when implemented. System-level account/workspace administration remains protected by the global System Admin flag.
- Roles grant capabilities; **Policies** also enforce workspace, project membership, ownership, and ticket-specific conditions.
- Hide unauthorized UI actions for usability, but enforce every access check on the server.

### Account & Invitation Flow
1. Users register/sign in with native Laravel sessions; email verification and password reset use built-in Laravel features.
2. System Admin creates/manages workspaces. Users may belong to multiple workspaces with different roles.
3. System Admin/Manager invites a user by email and assigns a workspace role.
4. The recipient signs in/registers, verifies email, and accepts the invitation.
5. Acceptance validates email, expiry and single use, then creates membership transactionally.
6. Removing membership revokes workspace access; it does **not** delete the global account.

Use hashed invitation tokens, login rate limits, CSRF protection, session regeneration, and audit events for security-sensitive changes.

## 4. Project & Ticket Processes

### Projects
- Archive preserves project membership/history and makes the project read-only until an actor with projects.setup restores it. P02 has no permanent project deletion.
- Manager/System Admin creates a project with a unique workspace-scoped **key** (e.g., `HRIS`) and name.
- Manager controls project membership and settings; Members and custom roles require explicit project membership. Only protected Managers and System Admins have workspace-wide project scope. Viewing still requires projects.view.
- A project's tickets, board, and members belong to its workspace. Never trust workspace or project IDs from the client without scoped authorization.

### Tickets
- Users need the corresponding tickets.view/create/edit/delete permission and project/workspace visibility. Ticket permissions do not implicitly grant project administration; ticket-only readers can discover their assigned boards from the workspace.
- Fields: project, sequential project number, title, description, type (Task, Bug, Story), priority (Low, Medium, High, Urgent), status, reporter, nullable assignee and due date, ordering, version and soft-deletion timestamps.
- Keys use PROJECTKEY-123, with numbers allocated under a project row lock and never reused after deletion. Project keys remain immutable.
- Select only active, verified, same-workspace project members with ticket access. Preserve historical assignee references when membership changes, but reject newly assigning an ineligible user.
- Server derives ownership, number, reporter and initial status; generic edits cannot change status. Archived projects are read-only.
- Creation, meaningful updates, assignment, deletion and moves commit with their audit records. Version checks reject stale writes.

### Configurable workflow and board
- Each project owns ordered statuses, exactly one initial status, and allowed directed transitions. Category (open/active/done) remains separate from display names.
- Default statuses: To Do (initial), In Progress, In Review, Done. Default transitions: To Do → In Progress; In Progress → To Do/In Review; In Review → In Progress/Done; Done → To Do.
- tickets.setup permits configuration within the same project scope. Preserve status IDs on rename/reorder; reject removing statuses referenced by any ticket, including deleted tickets.
- Kanban, grouped list and table read the same tickets. Backend filters, bounded columns and table pagination keep payloads small.
- Cross-column moves require a currently configured transition. Same-column moves change order only. The server uses a before-ticket anchor against the complete column and checks ticket/board versions before saving.
- UI follows docs/UI-UX.md and design-reference images: compact project tabs, calm sidebar, board columns, creation dialog, details drawer, dense list/table, semantic tokens and submission toasts. Excluded screenshot features remain absent.

## 5. Initial Data Model

| Table | Key fields / relationship |
|---|---|
| `users` | name, unique email, password, status, email_verified_at, `is_system_admin` |
| `workspaces` | name, unique slug |
| `roles` | workspace_id, name, unique workspace-scoped code, permissions JSON, is_system |
| `workspace_members` | workspace_id, user_id, role_id; unique (workspace_id, user_id) |
| `workspace_invitations` | workspace_id, email, role_id, token_hash, expires_at, accepted_at, invited_by |
| `projects` | workspace_id, name, immutable key, description, active/archived status, created_by, last_ticket_number, board_version, workflow_version; unique (workspace_id, key) |
| `project_members` | project_id, user_id; unique (project_id, user_id) |
| `tickets` | project_id, number, type, title, description, status_id, priority, reporter_id, assignee_id, due_date, sort_order, version, timestamps, deleted_at |
| `project_statuses` | project_id, stable code, name, category, position, is_initial, timestamps |
| `project_status_transitions` | project_id, from_status_id, to_status_id; unique directed pair per project |
| `activity_logs` | workspace_id, actor_id, action, subject_type, subject_id, changes JSON, created_at |

Enforce foreign keys, relevant uniqueness constraints, indexes for common filters, and server-side pagination. Use Eloquent relationships; add tables only for actual features. Activity logs serve both security and ticket history—avoid redundant audit tables initially.

## 6. Architecture & Implementation Rules

- **Laravel:** Routes → Controllers → Form Requests/Policies → Models. Add a focused service for invitation acceptance or other multi-record workflows; use `DB::transaction()` when consistency spans records.
- **Vue/Inertia:** One page per route; feature-first components; thin pages, focused forms/tables/dialogs. Use Inertia `useForm` and Vue `ref`/`computed` by default.
- **Data access:** Eloquent queries always scoped to the authorized workspace/project. Prefer server-side filtering and pagination for large ticket lists.
- **Board:** Use optimistic UI only with rollback/error handling; the server is authoritative for allowed transitions and ordering.
- **No unnecessary abstractions:** Avoid microservices, generic repository layers, separate API-only architecture, Pinia, and TanStack Query unless a demonstrated need arises.
- **Security:** Authorize on every server mutation; validate inputs; protect user status and global admin privileges; record important changes.

## 7. Delivery Order & Acceptance

| Phase | Deliverable |
|---|---|
| P01 | Native authentication, accounts, workspace management, roles, permissions, invitations |
| P02 | Projects, project membership, scoped access |
| P03 | Tickets, assignments, details, Kanban/list/table, configurable workflow/status transitions, ordering and audit history |
| P04+ | Future modules require a separately approved specification |
| P05+ | Optional notifications, attachments, reporting, agile features, integrations |

**Done means:** Backend validation and authorization pass; expected workflows work in-browser; tenant/project isolation is tested; relevant tests pass; and docs reflect implemented behavior.

**Agent workflow:** Inspect this SDD and the owning module spec → implement only the authorized scope → write/update tests → run backend tests and Playwright flows → fix failures → report changes, results, and remaining gaps. Document design changes before implementing behavior that contradicts this SDD.

## 8. Deferred Expansion

Keep ticket/activity boundaries extensible for eventual **GitHub and GitLab** integrations and optional agent-driven coding/testing workflows. Phase 1 contains **no autonomous AI execution**. Defer providers, SDKs, billing/metering, PR/MR automation, and event orchestration until separately specified.

---

**Authority:** This SDD describes planned behavior; implementation and completed work must be verified from code and tests, not inferred from this document.
