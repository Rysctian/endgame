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
3. **Ticket Management:** Ticket creation, editing, types, priority, assignee, due date, parent/subtask relationships, backlog.
4. **Kanban Board:** Status columns, drag-and-drop transitions, ordering and filters.
5. **Collaboration & Audit:** Ticket comments and activity history.

**Later:** Attachments, notifications, configurable workflows, sprints, epics/estimates, advanced reports, real-time collaboration, integrations, and agentic automation. Do not implement these unless separately authorized.

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
- Define module permissions centrally in Laravel configuration using `resource.action` keys and supported `view`, `create`, `edit`, `delete`, and `setup` actions. Store enabled keys as a JSON array on roles. Phase 01 registers workspace members, roles/permissions, and activity; future modules register actions when implemented. System-level account/workspace administration remains protected by the global System Admin flag.
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
- Manager/System Admin creates a project with a unique workspace-scoped **key** (e.g., `HRIS`) and name.
- Manager controls project membership and settings; Members see only projects they are authorized to access.
- A project's tickets, board, and members belong to its workspace. Never trust workspace or project IDs from the client without scoped authorization.

### Tickets
- Users with permission create tickets within accessible projects.
- MVP fields: project, sequential project-scoped ticket number, title, description, type, status, priority, reporter, assignee (nullable), due date (nullable), parent ticket (nullable), timestamps.
- Ticket key format: `PROJECTKEY-123` (stable and unique); generate numbers safely under concurrency.
- Recommended MVP types: **Task, Bug, Subtask**. Add Story/Epic only when planning features require them.
- Assignees must have access to the project; subtasks belong to the same project as their parent.
- Only authorized users may edit, assign, delete, or change ticket status.

### Default Workflow

`Backlog → To Do → In Progress → In Review → Done`

- **In Review → In Progress** when changes are requested.
- **Done → To Do** when a ticket is reopened by an authorized user.
- Kanban displays persisted tickets grouped by status; moving a card updates status/order through a validated Laravel request.
- For MVP, seed fixed statuses and permitted transitions. Make workflow configuration a later module; do not build a generic workflow engine prematurely.
- Log meaningful changes, including status, assignee, priority, and membership changes.

## 5. Initial Data Model

| Table | Key fields / relationship |
|---|---|
| `users` | name, unique email, password, status, email_verified_at, `is_system_admin` |
| `workspaces` | name, unique slug |
| `roles` | workspace_id, name, unique workspace-scoped code, permissions JSON, is_system |
| `workspace_members` | workspace_id, user_id, role_id; unique (workspace_id, user_id) |
| `workspace_invitations` | workspace_id, email, role_id, token_hash, expires_at, accepted_at, invited_by |
| `projects` | workspace_id, name, key, description; unique (workspace_id, key) |
| `project_members` | project_id, user_id; unique (project_id, user_id) |
| `tickets` | project_id, number, parent_id, type, title, description, status, priority, reporter_id, assignee_id, due_at, position |
| `ticket_comments` | ticket_id, user_id, body, timestamps |
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
| P03 | Tickets, assignments, backlog, ticket details/comments |
| P04 | Kanban board, status transitions, ticket ordering, audit history |
| P05+ | Optional notifications, attachments, reporting, agile features, integrations |

**Done means:** Backend validation and authorization pass; expected workflows work in-browser; tenant/project isolation is tested; relevant tests pass; and docs reflect implemented behavior.

**Agent workflow:** Inspect this SDD and the owning module spec → implement only the authorized scope → write/update tests → run backend tests and Playwright flows → fix failures → report changes, results, and remaining gaps. Document design changes before implementing behavior that contradicts this SDD.

## 8. Deferred Expansion

Keep ticket/activity boundaries extensible for eventual **GitHub and GitLab** integrations and optional agent-driven coding/testing workflows. Phase 1 contains **no autonomous AI execution**. Defer providers, SDKs, billing/metering, PR/MR automation, and event orchestration until separately specified.

---

**Authority:** This SDD describes planned behavior; implementation and completed work must be verified from code and tests, not inferred from this document.
