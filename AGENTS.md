# AGENTS.md

## Prerequisites
- PHP 8.3+, Composer, Node.js
- SQLite (default) — no external DB needed for local dev

## Commands
| Task | Command |
|------|---------|
| Full setup (install deps, migrate, build) | `composer setup` |
| Start dev (server + queue + logs + Vite) | `composer dev` |
| Run all tests | `composer test` |
| Run a single test | `php artisan test --filter=TestName` |
| Lint PHP | `vendor/bin/pint` |
| Build frontend | `npm run build` |

## Architecture

### Roles & Access
- **admin** — full CRUD: create weekly plans, close/validate, view dashboard & rankings
- **manager** / **director** — view-only: dashboard, rankings
- **spv** — no web UI; assigned as plan owners, accessible via API (Sanctum)

Role checks use `RoleMiddleware` aliased as `role` in `bootstrap/app.php`. Routes in `web.php` gate with `middleware(['auth', 'role:admin,manager,director'])`.

### Database
- SQLite file: `database/database.sqlite` (auto-created by setup if missing)
- Migrations define the schema; no manual SQL needed
- `DatabaseSeeder` calls `DepartmentSeeder`, `UserSeeder`, `AuthUserSeeder`, `DevelopmentDailyReportSeeder`

### Key Database Enums
- `users.role`: `admin`, `director`, `manager`, `kabag`, `spv` (authorization/access)
- `area_assignments.role` (`App\Enums\Position`): `manager`, `kabag`, `spv` (organizational responsibility)
- `work_items.work_type` (`App\Enums\WorkType`): `routine`, `problem_solving`, `improvement`, `strategic_improvement`
- `weekly_plans.status`: `planned`, `completed`, `completed_no_impact`, `not_completed`, `extended`
- `weekly_plans.category`: `improvement`, `problem`, `maintenance`
- `weekly_plans.impact_level`: `low`, `medium`, `high`

### Score Calculation
Triggered automatically via `WeeklyPlanObserver@updated` when status changes **from** `planned`. Logic lives in `ScoreCalculatorService`:
- `completed` → base 100, `completed_no_impact` → 60, `extended` → 40, `not_completed` → 0
- Multiplier: `low` ×1.0, `medium` ×1.2, `high` ×1.5
- Final score = base × multiplier, persisted to `plan_scores` via `updateOrCreate`

### File Uploads
- Proof images stored on `public` disk (`storage/app/public/proofs/`)
- Validation: image files, max 10MB, required when status is `completed` or `completed_no_impact`
- Don't forget `php artisan storage:link` if not already linked

### Models to Know
- `User` — `isAdmin()`, `isManager()` (returns true for both `manager` and `director`); `areaAssignments()`, `assignedAreas()`
- `Area` — stable operational identity (`code` immutable); `assignments()`, `dailyReports()`, `workItems()`, `issues()`
- `AreaAssignment` — `activeOn(Carbon)` for inclusive-date membership
- `WeeklyPlan` — has `proofs()`, `score()`, `creator()`, `updater()` relationships
- `PlanProof` — `$timestamps = false`, manual `uploaded_at` column
- `PlanScore` — `$timestamps = false`, manual `calculated_at` column

## Master Data Foundation

### Department & Area Identity
- `departments.code` and `areas.code` are **immutable** business identities: unique across all history, never reused. Only `name` may change.
- Code immutability is enforced in each model's `booted()` `saving` hook (throws `LogicException` on `isDirty('code')`), matching the `WorkItem` immutable-field pattern.
- A rename changes `name` only; a genuinely new unit gets a new code.

### Active / Inactive Lifecycle
- `departments` and `areas` carry `is_active` (default true) and `deactivated_at` (nullable).
- Deactivate, never delete: historical records keep referencing inactive entities.
- `activate()` / `deactivate()` / `reactivate()` model methods. `::active()` feeds new-transaction selectors; `::inactive()` remains queryable.
- No `deactivated_by` — the existing `updated_by` audit field covers *who*.

### Area Assignment (`area_assignments`)
- Fields: `area_id`, `user_id`, `role` (`App\Enums\Position`: `manager`, `kabag`, `spv`), `started_at`, `ended_at` (nullable = current).
- One area may hold multiple simultaneous assignments (e.g. KABAG + SPV); one user may hold many assignments.
- `ended_at` is **INCLUSIVE**: active on date D when `started_at <= D AND (ended_at IS NULL OR ended_at >= D)` — see `AreaAssignment::activeOn()`.
- Authorization role (`users.role`) is distinct from organizational position (`area_assignments.role`).

### Daily Report Identity
- Identity = `reported_by + area_id + report_date`. One reporter may submit multiple reports on one date for different areas.
- `daily_reports.area_id` is nullable only for legacy transition; new records require it at the validation layer (`StoreDailyReportRequest`).
- The legacy `unique(reported_by, report_date)` index was dropped; the final `unique(reported_by, area_id, report_date)` will be added by a dedicated migration **after** organizational backfill (a UNIQUE constraint can't be applied while `area_id` is NULL). Duplicate `(reporter, area, date)` is enforced at the validation layer until then.

### Historical Snapshot Invariant
- `areas.department_id` is the **current** relationship and may change.
- `daily_reports` / `work_items` / `issues` store **frozen** `department_id` / `area_id` (and `owner_id` / `reported_by`) = context at transaction creation. Never reconstruct historical truth from current master state.

### No Organizational Backfill Yet
- No `areas` have been derived from the legacy `departments` table; no real area mappings or `area_assignments` exist. The authoritative organizational mapping is supplied separately (controlled import + dry-run + confirmation).

### Audit Fields (created_by / updated_by)
New-domain tables (`daily_reports`, `work_items`, `issues`, `work_item_schedule_changes`) carry explicit `created_by` / `updated_by` FK columns. These are assigned explicitly by the application/service/controller layer (e.g. `created_by => auth()->id()`), **not** via a global model observer. `reported_by` (on `daily_reports`) is distinct: it identifies the person whose report the record belongs to.

## Testing
- In-memory SQLite (`:memory:`), array cache/session, sync queue
- Two test suites: `tests/Unit` and `tests/Feature`
- Only one placeholder test each currently (`ExampleTest`)

## Style Conventions
- PHP: 4-space indent, UTF-8, LF line endings (via `.editorconfig`)
- Uses Laravel Pint for code style (no custom rules file)
- Blade views use CDN-loaded Tailwind CSS (not Vite) for `app.blade.php` and most auth'd views; `vite.config.js` is wired for `resources/css/app.css` and `resources/js/app.js` but views don't `@vite()` them

## Seeded Users
| Email | Password | Role | Department |
|-------|----------|------|------------|
| `adminppic@peroniks.com` | `password` | admin | PPIC |
| `mr@peroniks.com` | `password123` | manager | Management |
| `direktur@peroniks.com` | `peronijayajaya123` | manager (treated as director) | Board |
| `admin@kaizen.com` | `password` | admin | — |
| `spv_a@kaizen.com` | `password` | spv | Production |

## Gotchas
- `direktur@peroniks.com` has role `manager` in DB but is conceptually "director" — `isManager()` returns true
- `AuthUserSeeder` is **not** called by `DatabaseSeeder`; it exists standalone (`php artisan db:seed --class=AuthUserSeeder`)
- No CI/CD configured, no GitHub workflows
- `composer dev` runs 4 processes (server, queue, logs, Vite) via `concurrently` — kill with Ctrl+C
