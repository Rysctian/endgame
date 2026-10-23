# Endgame — Frontend Conventions

## Stack
- Vue 3, TypeScript, Composition API (`<script setup lang="ts">`), Inertia.js 3.
- Tailwind CSS 4, shadcn-vue, Lucide icons.
- Laravel owns routing, authentication, validation, authorization, and business rules.
- Use Inertia props and forms by default. Do not add Vue Router, global state, or a separate API/data-fetching layer without a concrete need.

## Structure
Keep the structure shallow and feature-first:

```text
resources/js/
├── pages/                 # One page per Inertia route
│   ├── auth/login.vue
│   └── settings/profile.vue
├── features/
│   └── user-access/
│       ├── member-table.vue
│       ├── member-dialog.vue
│       └── role-form.vue
├── components/ui/         # shadcn-vue
├── layouts/
├── lib/
└── types/
```

- Use **kebab-case** filenames and PascalCase Vue component tags.
- Keep feature-specific components and types inside their feature. Share only genuinely reusable code.
- Follow the configured Inertia page resolver and existing repository conventions; do not restructure working code without a reason.
- Extract components when they serve a clear responsibility or a page becomes difficult to read. Avoid unnecessary nested folders and abstractions.

## Components and State
- Use typed props/emits and small, readable components; avoid `any` when practical.
- Use `ref`/`reactive` for local state, `computed` for derived values, and watchers only for side effects.
- Create composables for meaningfully reused reactive logic, not every handler.
- Prefer explicit data flow over event buses or global stores.

## Forms and Data
- Use Inertia `<Form>` or `useForm` for ordinary submissions.
- Show validation errors, saving states, and clear success/failure feedback.
- Receive business options (roles, permissions, project choices, etc.) as typed, authorized Inertia props. Do not duplicate configurable business rules in Vue.
- Keep forms explicit and readable; do not build a generic form-rendering engine.
- Frontend permission checks affect visibility only; Laravel must enforce access and validate submitted data.
- **Hide** unauthorized actions; **disable** authorized actions only when temporarily unavailable.

## UI and UX
- Reuse shadcn-vue components and Tailwind utilities before creating custom controls.
- install and utilize shadcn's different components  (calendars, combobox for dropdowns, dropdown menu, range calendar), maximize drawers and dialogs for forms to avoid too much pages.
- Install only components needed by an approved feature; use dialogs/drawers for short contextual forms and pages when the workflow warrants them.
- Favor clean layouts, clear labels, consistent spacing, keyboard accessibility, and one primary action per view.
- Avoid unnecessary descriptions, decorative icons, excessive dialogs, and custom CSS when existing utilities suffice.Let the layout do the explaining.
- Follow current project design tokens and established shadcn-vue patterns rather than redesigning unrelated screens.

## Tables and Performance
- Use TanStack Vue Table for interactive data tables that need sorting, filtering, or selection; use simple markup for trivial tables.
- Keep table columns and filters near the feature.
- Use server-side pagination, filtering, and sorting for large datasets.
- Avoid duplicated reactive state, oversized page props, and unnecessary network calls.

## Verification
- Add focused Vitest tests for meaningful UI logic and Playwright tests for critical user flows.
- Run relevant available checks; never claim tests passed unless executed.
- Follow `AGENTS.md`, `docs/SDD.md`, and the approved module specification. Do not implement unplanned features.

**Rule: simple components, clear data flow, shallow folders.**
