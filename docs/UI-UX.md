# Endgame — UI/UX Design Reference

## Purpose
Use the screenshots in `design-reference/` as the **visual reference** for Endgame's project and task management interface. Match their layout, spacing, density, interaction patterns, and overall visual language. Build the behavior from the approved module specifications—not from the screenshots alone.

## Reference Images

**Local reference folder:** `C:\Users\rysch\OneDrive\Desktop\endgame\design-reference`

**Repository-relative folder:** `design-reference/` (assuming this file is saved at `docs/UI-UX.md`, image paths begin with `../design-reference/`).

| Screenshot | What to reference |
|---|---|
| [`projects module.png`](../design-reference/projects%20module.png) | Workspace Projects page: card grid, compact project metadata, status badges, progress indicators, member avatars, search/filter/view controls. |
| [`overview.png`](../design-reference/overview.png) | Project overview: header and tabs, two-column summary, About, Progress, Coming Up, Details, Milestones, Recent Activity. |
| [`kanban.png`](../design-reference/kanban.png) | Horizontal board: compact status columns, draggable task cards, column counts, filters, inline Add Task actions. |
| [`list-tab.png`](../design-reference/list-tab.png) | Grouped task list: collapsible status sections, compact rows, inline task creation, lightweight columns. |
| [`table-tab.png`](../design-reference/table-tab.png) | Dense editable table: status, priority, assignee, dates, labels, estimate; inline menus and horizontal scrolling. |
| [`create-ticket-dialog.png`](../design-reference/create-ticket-dialog.png) | Centered quick-create dialog: title/description first, compact property controls, date picker, subtasks, attachments, footer actions. |
| [`ticket-edit-drawer.png`](../design-reference/ticket-edit-drawer.png) | Right-side task details panel: task metadata, overdue notice, description, subtasks, attachments, comments, activity. |
| [`activity-tab.png`](../design-reference/activity-tab.png) | Chronological activity feed: grouped dates, small avatars, human-readable change events and timestamps. |

Inspect the relevant image **before building or revising** its screen. If an image is missing from the repository, report it; do not claim you reviewed it.

## Overall Design Language

- **Style:** Calm, modern, minimal, professional productivity software. Dense enough for serious daily use, but not visually crowded.
- **Colors:** White main canvas; warm off-white/light-gray sidebar and board columns; subtle gray borders; dark charcoal text; muted secondary labels. Use one restrained raspberry/magenta accent for primary actions and selected highlights. Use semantic colors for status, urgency, and overdue dates.
- **Typography:** Compact sans-serif, clear hierarchy: medium-weight page titles, smaller section headings, restrained metadata. Avoid oversized headings and unnecessary descriptions.
- **Surfaces:** Thin borders, small-to-medium corner radii, minimal shadows, generous whitespace between sections but compact spacing within records.
- **Components:** Consistent icons, badges, avatars, inputs, compact menus, and subtle hover/focus states. Prefer clear text labels to excessive icon-only actions.
- **Copy:** Keep interface labels short and purposeful. Do not add decorative headings, redundant descriptions, or unnecessary helper text.

## Shared App Layout

- **Left sidebar:** Persistent on desktop, warm-neutral background, compact logo/workspace switcher, grouped navigation, project shortcuts, settings/profile near bottom. Highlight the active destination subtly.
- **Top toolbar:** Thin horizontal header with breadcrumbs or current section; keep search and quick actions accessible without clutter.
- **Project header:** Project icon, name, status and optional favorite control; member avatars and authorized actions to the right. Under it, a compact tab bar for available project views.
- **Content:** Wide, white working area. Lists and tables use the width; overview content is narrower and centered for readability.
- **Contextual editing:** Prefer inline edits for simple values, a dialog for quick creation, and a right-side drawer for rich task details. Avoid unnecessary navigation to separate edit pages.
- **Responsive behavior:** Collapse the sidebar on smaller screens. Permit horizontal scrolling for Kanban and wide tables; do not squeeze every column into unreadable widths. Drawers/dialogs should adapt to narrow screens.

## Screen Behavior

### Projects
Show an easy-to-scan grid of project cards. Each card prioritizes project name, concise description, project status (only if defined by the approved specification), progress from real data, and useful metadata. Support search and available filters; keep actions obvious. Avoid speculative metrics.

### Project Overview
Use a balanced two-column layout: main information and work summaries on the left; concise supporting details and recent activity on the right. Summaries must reflect actual data and defined calculations. Hide sections that are not yet supported by approved module processes.

### Kanban
Render one column per configured workflow status, with count and task cards. Cards show the most useful metadata without becoming miniature forms. Allow drag-and-drop **only through a validated backend transition**. On rejection, restore the card and show a clear message. Handle empty columns and horizontal overflow gracefully.

-make sure the kanban draggable has smooth animation

### List and Table
The list view groups tasks by workflow status and allows easy scanning and expansion. The table view supports denser comparison, meaningful columns, sorting/filtering, and inline edits where authorized. Use server-side filtering/pagination for large datasets. Both views use the **same underlying tickets and workflow rules** as Kanban.

### Task Creation
Use a focused dialog with required title and optional description, then compact selectors for approved fields (project, status, assignee, priority, dates, etc.). Use shadcn-vue calendar/popover, combobox, and select components where appropriate. Present validation beside fields, preserve form values on errors, and prevent double submission. Do not add recurrence, estimates, subtasks, or labels unless those features are approved.

### Task Details
Open a right-side drawer without losing the user's position in the current view. Put title and primary status/actions at the top; show metadata in an orderly list; place description, subtasks, files, comments, and history below **when supported**. Make the drawer independently scrollable. Inline edits require permission checks and server validation.

### Activity
Display meaningful actor/action/target changes in reverse chronological order, grouped by date where useful. Use small avatars, concise human-readable descriptions, and timestamps. Show authorized activity only; avoid noisy or duplicate entries.

## Interaction and Accessibility Rules

- Use **shadcn-vue**, Tailwind CSS 4, Vue 3, TypeScript, and Inertia conventions already chosen for Endgame. Prefer built-in design primitives before creating custom controls.
- Use `vue-draggable-plus` for board interactions and TanStack Vue Table where a real data table is useful; avoid complicated wrappers.
- Consistent hover, focus, loading, disabled, empty, success, and validation states are required.
- Hide actions without permission; disable only actions that are permitted but currently unavailable. Laravel enforces permissions and workflow rules.
- All menus, date pickers, dialogs, and drawers need keyboard access, labels, and appropriate focus management.
- Do not copy the reference application's branding, demo text, or sample data into Endgame. Use Endgame naming and actual module data.
- **Visual references do not authorize features.** Build only approved functionality in `docs/SDD.md` and `docs/modules/*.md`; future tabs shown in screenshots may remain absent until specified.


## Submission Feedback — Toasts

- Use the existing **Vue Sonner / shadcn-vue Sonner** toast component for all **state-changing submissions**: create, update, delete, invite, assign, status changes, and similar actions.
- Show a brief **success toast** after the server confirms success; show an **error toast** when a submission fails. Use **warning** or **info** to communicate meaningful non-error outcomes when appropriate.
- Keep Laravel field-validation errors **next to their fields**; a concise error toast may summarize the failure but must not replace inline errors.
- Show only **one clear toast per action**; avoid duplicate messages from Inertia redirects, flash messages, and form callbacks.
- Write short, specific messages (e.g., “Project created”, “Unable to update ticket”). Do not announce success before persistence is confirmed.
- Keep submit buttons disabled or loading while pending; preserve form input on failure. Do not show toasts for passive navigation or ordinary input changes.


## Guidance for Codex

1. Read `AGENTS.md`, `FRONTEND.md`, the relevant module blueprint, and this file.
2. Inspect the matching screenshots from `design-reference/` before working on that screen.
3. Follow the screenshot's design language and layout without copying unapproved functionality.
4. Keep components feature-first, shallow, reusable where meaningful, and straightforward to maintain.
5. Verify responsive behavior, core interactions, keyboard access, permission states, and real data rendering; use Playwright for important flows.

**Design priority:** Clear hierarchy → predictable interaction → compact, readable information → visual consistency. Keep the implementation simpler than the screenshots if the approved scope is smaller.Let the layout do the explaining.
