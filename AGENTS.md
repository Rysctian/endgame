# Endgame — Agent Instructions

## Purpose
Endgame is a Jira-inspired task management application built with Laravel, Vue 3, Inertia, and TypeScript. Prioritize readable, maintainable code and avoid overengineering.

## Before Working
- Read `docs/SDD.md` and the relevant module specification, if present.
- For backend changes (PHP, controllers, models, database, authorization), read `BACKEND.md`.
- For frontend changes (Vue, Inertia, UI, styling), read `FRONTEND.md`.
- For full-stack changes, read both `BACKEND.md` and `FRONTEND.md`.
- Follow the approved tech stack and existing project conventions.
- Inspect related code and tests before making changes.
- Implement only approved requirements. Do not invent processes for modules that are not yet specified.
- If a referenced document is missing or conflicts with the request, report it instead of assuming its contents.

## Module Documentation
- Store module specifications in `docs/modules/`.
- Maintain **one Markdown file per core module**, using kebab-case filenames (e.g., `user-access-management.md`).
- Each file is the **single source of truth for that module's approved processes and implemented behavior**.
- Document all relevant functionality, process flows, Mermaid flowcharts, business rules, validations, permissions, database schema, relationships, and important edge cases.
- Keep documentation detailed enough to understand and rebuild the module, but concise, structured, and easy to read.
- Before implementing an approved change, update the affected module specification when its design or behavior changes.
- After implementation and testing, reconcile the documentation with the actual code.
- **Always maintain the latest complete blueprint**, not incremental patches or appended revision notes.
- Integrate revisions directly into the relevant sections. Remove obsolete behavior and update affected diagrams, rules, and schemas.
- Keep planned and implemented functionality clearly distinguished. Never document unfinished functionality as implemented.
- Do not create separate documentation files for small changes to an existing module.
- Update `docs/SDD.md` only when system-level architecture, module scope, or major design decisions change.

## Implementation Rules
- Work on one task at a time; make the smallest complete change.
- Keep functions, components, and folders simple and focused.
- Do not add packages, abstractions, repositories, DTOs, or service layers without a concrete need.
- Follow the existing naming, formatting, and package-manager conventions.
- Do not refactor unrelated code or run destructive database commands without approval.

## Laravel
- Use native session authentication, hashing, validation, password broker, notifications, policies/gates, and rate limiting. No extra authentication or permissions package.
- Prefer conventional routes, controllers, Form Requests, Eloquent models, and policies.
- Add focused services only for multi-step business operations; use database transactions when multiple records must change together.
- Validate inputs and enforce all permissions server-side.
- Protect System Admin access and ownership. Managers must not grant themselves or others system-level privileges.
- Scope workspace data and membership changes to the authorized workspace.

## Vue / Inertia
- Use Vue 3 `<script setup lang="ts">`, TypeScript, and Inertia page props/forms.
- Keep one page component per route and feature-specific components near their feature.
- Use Vue's built-in reactivity; avoid global state and duplicate API fetching unless needed.
- Prefer existing shadcn-vue components.
- Hide actions the user cannot perform; disable actions only when temporarily unavailable.
- Frontend permission checks are for UX; Laravel remains the authority.

## Tests and Completion
- Test relevant behavior, validation, authorization, and edge cases.
- Use Playwright for important end-to-end flows when appropriate.
- Run applicable tests, formatting, and type checks using available project scripts.
- Never claim a test passed unless it was run.
- Report changes, verification results, and unresolved issues.
- Update the relevant specification if an approved requirement or design changes.

## Current Scope
- **Specified:** User & Access Management (accounts, workspace membership, invitations, roles, and permissions).
- **Not yet specified:** Other modules. Do not implement them without an approved specification.
