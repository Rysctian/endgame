# ENDGAME

Foundation installed from [tech-stack.md](tech-stack.md): Laravel 13, PHP 8.5.12, Vue 3 with TypeScript and Inertia 3, Tailwind CSS 4, shadcn-vue, Lucide Vue, date-fns, Vite, pnpm, Composer, and MySQL 8.4.13 LTS.

Phase 01 implements native session authentication, account settings, email verification and password recovery, System Admin account/workspace administration, workspace memberships, custom roles, invitations and security activity. Project and ticket features remain deferred. See [the module blueprint](docs/modules/user-access-management.md).

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

The repository bundles MySQL 8.4.13 on `127.0.0.1:3384`. The application connects to the database configured in `.env`; this checkout currently uses the existing `endgame2` database on port `3307`. Generated credentials for the bundled instance are stored in ignored local configuration files, including `.local/admin.cnf`; application connection credentials remain in the ignored `.env`. Apply outstanding forward migrations with `.\php.cmd artisan migrate`; existing data is preserved.

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

The browser suite uses Chromium and starts a temporary Laravel server on a checked, randomly selected local port with a newly created SQLite database under `storage/framework/testing/`. It does not use the development MySQL database. Build assets before running it. Install the browser on a fresh machine with `pnpm.cmd exec playwright install chromium`.

PHPUnit uses an isolated in-memory SQLite database for fast tests. The development application uses MySQL. Vitest is installed and configured for future `resources/js/**/*.test.ts` files; `pnpm.cmd run test:unit` currently permits an empty suite.

shadcn-vue is initialized in `components.json`, with a Button component installed. Add components with `pnpm.cmd exec shadcn-vue add <component>`.


## First System Admin and demo data

Apply the schema, then provision your own administrator with the explicit console command. New accounts prompt for a password (12 characters minimum). Promoting an existing account preserves its password, status and verification state.

```powershell
.\php.cmd artisan migrate
.\php.cmd artisan app:make-admin you@example.com --name="Your Name"
```

Configure Laravel's mail transport in .env so verification, recovery and invitation emails can be delivered. The log mailer is suitable for local development only. Do not publish the application with debug mode enabled.

Local/testing demo data is available through the default seeder:

```powershell
.\php.cmd artisan db:seed
```

This creates five demo users, five workspaces, five roles per workspace, five members per workspace, five invitations per workspace, and five activity records per workspace. It also creates five projects per demo workspace and assigns its active demo members to newly created projects. Project edits, archive state, and membership changes survive reruns. Demo users use demo-password-123, remain ordinary users, and are only created when their email does not already exist. Rerunning preserves existing credentials and relationships. The seeder runs automatically only in local/testing environments.

On restricted Windows environments, Vite's default config bundler may fail directory access checks. Use pnpm.cmd exec vite build --configLoader runner (or invoke Vite with --configLoader runner directly). The tests themselves require no network access once Chromium is installed.

Project Management (P02) is available from each authorized workspace’s **Projects** link. Its complete blueprint is in `docs/modules/project-management.md`. Apply the forward migration with `.\php.cmd artisan migrate`; optionally populate new project tables with `.\php.cmd artisan db:seed --class=DemoProjectSeeder`. Project field metadata, status filters, and eligible member options are supplied by Laravel.
