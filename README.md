# ENDGAME

Foundation installed from [tech-stack.md](tech-stack.md): Laravel 13, PHP 8.5.12, Vue 3 with TypeScript and Inertia 3, Tailwind CSS 4, shadcn-vue, Lucide Vue, date-fns, Vite, pnpm, Composer, and MySQL 8.4.13 LTS.

This is a clean Laravel application with Vue/Inertia integrated manually. Authentication uses Laravel's existing session guard and built-ins; no authentication package or authentication screens have been added. Task management features are not implemented yet.

## Start on this Windows computer

From this folder in PowerShell:

```powershell
.\dev.cmd
```

Open http://127.0.0.1:8000. This starts the local MySQL instance, Laravel server, queue worker, and Vite. Press Ctrl+C to stop the development servers. MySQL stays running; stop it with `.\mysql.cmd stop`.

PHP 8.5.12 is installed in `.local/php`. Use the project wrappers to select it; the system's PHP installation is unchanged:

```powershell
.\php.cmd -v
.\php.cmd artisan migrate
.\composer.cmd install
pnpm.cmd install --frozen-lockfile
```

Use `pnpm.cmd` in PowerShell because this computer blocks `.ps1` launchers. No global execution policy was changed.

## Database

MySQL 8.4.13 runs on `127.0.0.1:3384`, with database `endgame` and a dedicated `endgame` user. Generated application credentials are in `.env`; generated administrator credentials are in `.local/admin.cnf`. These files are ignored by Git. The initial Laravel migrations have been applied.

```powershell
.\mysql.cmd start
.\mysql.cmd stop
```

The local PHP/MySQL binaries and database data live in the ignored `.local` directory. On another machine, install PHP 8.5.12 or newer within 8.5, Composer, Node 22.12+, pnpm 10.19, and MySQL 8.4; copy `.env.example`, provide that machine's database credentials, generate an app key, install dependencies, and migrate. The Windows wrappers depend on this local installation; regular `php` and `composer` commands work with equivalent runtimes elsewhere.

## Checks

```powershell
.\composer.cmd validate --strict
.\composer.cmd check-platform-reqs
.\php.cmd artisan test
pnpm.cmd run typecheck
pnpm.cmd run build
pnpm.cmd run test:e2e
```

The browser smoke test uses Chromium and starts a temporary Laravel server on port 8001. Build assets before running it. Install the browser on a fresh machine with `pnpm.cmd exec playwright install chromium`.

PHPUnit uses an isolated in-memory SQLite database for fast tests. The development application uses MySQL. Vitest is installed and configured for future `resources/js/**/*.test.ts` files; `pnpm.cmd run test:unit` currently permits an empty suite.

shadcn-vue is initialized in `components.json`, with a Button component installed. Add components with `pnpm.cmd exec shadcn-vue add <component>`.
