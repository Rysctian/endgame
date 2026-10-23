# ENDGAME (a task management app)— Tech Stack

## Architecture
- **Modular monolith:** One Laravel application with Vue pages rendered through Inertia.
- **Principles:** Fast, maintainable, minimal dependencies. No separate frontend API or unnecessary abstractions.

## Core
| Layer | Technology |
|---|---|
| Backend | Laravel 13 (PHP 8.5, latest patch) |
| Frontend | Vue 3 (Composition API) + TypeScript |
| Routing | Inertia.js 3 |
| UI | latest shadcn-vue + Tailwind CSS 4 + Lucide Vue |
| Database | MySQL 8.4 LTS + Eloquent |
| Build & packages | Vite + pnpm + Composer |

## Frontend Libraries
- **date-fns:** Dates and deadlines.
- Use **Inertia forms** and Vue's built-in reactivity. Add state libraries only if necessary.

## Authentication & Authorization
**Laravel built-ins only — no extra authentication package.**
- Session guard, hashing, Form Requests, password broker, email verification.
- Notifications, rate limiter, Policies and Gates.
- Conventional controllers and explicit Inertia forms.
- Focused services and database transactions only for multi-record operations (e.g., invitations).

## Testing & Agent Workflow
- **PHPUnit or Pest:** Backend tests.
- **Playwright:** Browser/end-to-end tests for AI agents and CI.
- **Vitest:** Frontend unit tests when needed.


**Setup note:** Start from clean Laravel and integrate Vue/Inertia manually;
