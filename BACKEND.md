# Backend Coding Conventions — Endgame

## Principles
- Follow Laravel conventions. Prefer readable, maintainable code over unnecessary abstractions.
- Simple CRUD: **Route → Controller → Model**.
- Multi-step business operations: **Route → Controller → focused Service → Model**.
- Add services only for meaningful business rules, transactions, or integrations. Do not create repositories, DTOs, interfaces, or action classes by default.
- Keep methods focused, naming descriptive, and folder nesting shallow.

## Controllers and Validation
- Use named routes and resource controllers where appropriate.
- Keep controllers responsible for requests, authorization, coordination, and Inertia responses or redirects.
- Use Form Requests when validation or authorization warrants separation; simple rules may stay in controllers.
- Return only the required, authorized Inertia props; paginate large datasets.
- Never trust client-provided roles, permissions, ownership, or other protected fields.

## Authentication and Authorization
- Use Laravel's built-in session guard, hashing, password broker, notifications, policies/gates, and rate limiter. **No additional authentication or permission package.**
- Enforce authentication, active account status, workspace membership, and permissions on the server.
- Use policies for model actions and gates for general capabilities. Keep permission keys consistent.
- **System Admin** has protected system-wide authority. Managers may manage roles only within their allowed scope and cannot grant System Admin access.
- Scope queries and record assignments to authorized workspaces. Avoid cross-workspace data exposure.

## Models, Queries, and Form Data
- Use Eloquent for relationships, casts, scopes, and straightforward model behavior.
- Keep complex multi-record workflows out of model accessors and observers.
- Eager-load needed relationships; prevent N+1 queries. Allowlist sortable/filterable fields.
- Pass business-specific choices and labels from Laravel as small, typed Inertia props when they are data-driven. Do not build a generic form-schema engine.
- Validate submitted choices again on the backend; frontend options do not grant permission.

## Database and Migrations
- Use MySQL with proper foreign keys, unique constraints, indexes, and column types.
- Use `DB::transaction()` when related writes must succeed or fail together.
- While a local database is disposable, prefer updating original create-table migrations over accumulating development-only patch migrations.
- Once migrations are shared, deployed, or used on a database that must be preserved, create new forward migrations instead.
- **Never run destructive database commands** (`migrate:fresh`, reset, truncate, etc.) without explicit approval and verification of the target database.
- Update affected models, factories, seeders, and tests when schemas change.

Rules:
- Keep migration history clean and understandable.
- Prefer updating the original `create_*` migration for schema changes when the database is disposable.
- Avoid unnecessary `update_*`, `alter_*`, or `revise_*` migrations during this stage.
- Merge obsolete development-only patch migrations into the original table creation migration when safe.
- Update affected models, factories, and seeders.
- Maintain valid foreign-key creation order.
- Preserve appropriate constraints and indexes.


## Factories, Seeders, and Jobs
- Create factories and seeders with realistic sample data for every applicable application table, including valid relationships and meaningful values.
- Keep required system data separate from optional demo data.
- Make seeders safely repeatable; never overwrite real accounts or credentials.
- Use queued jobs only for work that should not block requests. Dispatch after commit when jobs depend on newly written records.
- Keep secrets out of code, Inertia props, and logs.

## Testing and Completion
- Follow `AGENTS.md`, `docs/SDD.md`, and the relevant approved module specification.
- Test validation, authorization, workspace isolation, and important success/failure paths using the project's configured test framework.
- Run relevant tests and formatting (Laravel Pint when configured). Report any checks that could not run.
- Change only what the task requires. Do not implement unapproved modules or refactor unrelated code.


## Module-owned form and selection data
- Each business module owns the **data contract** its forms and filters need: available select options, available input fields and required/read-only flags when configurable, field labels, defaults, allowed actions, and validation rules.
- Prepare these values on the Laravel side and pass them as **small, clearly named Inertia page props** (for example, `workTypes`, `priorities`, `assignees`, `labels`, `fieldLabels`, `fieldStates`, `defaults`). Keep this preparation near the module's controller or focused query/helper; do not build a generic form-schema engine.
- Dropdown options should use a consistent, simple shape such as `{ value, label }` where useful. Provide stable IDs/keys for submitted values and human-readable labels for display.
- Query only options the current user is allowed to select, restricted to the active project and record state. Do not send full models or unrelated columns merely to populate a select.
- When modules have configurable fields or labels, the backend supplies their metadata; do not duplicate those rules or option lists in Vue. Static UI copy that is not business-configurable may stay in the frontend.
- For large or dependent selects, use a small authorized search endpoint or an Inertia partial reload **only when needed**; do not preload thousands of options.
- **The submitted option must still be validated on the server** against current membership, project, status, and allowed choices. Displayed options are not authorization or a substitute for validation.
- Keep data contracts explicit and module-specific. Do not introduce a generic metadata framework, repositories, or services solely to pass dropdown data.

**Rule: Readable and correct beats abstract and clever.**
