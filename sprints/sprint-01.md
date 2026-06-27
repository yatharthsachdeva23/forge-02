# Sprint 01 — Backend Foundation & Multi-Tenant Database Isolation

**Goal:** Stand up the Laravel 11 API base, create all core database tables, and enforce hard multi-tenant isolation so that no query can ever leak data across organizations.

**Models:** Hermes = `deepseek/deepseek-v4-pro` (orchestrator / product owner) · OpenClaw = `z-ai/glm-5.1` (developer)
**Repo:** `github.com/yatharthsachdeva23/forge-02` (branch `main`)
**State at sprint start:** `/backend` is empty (only `.gitkeep` + `.env.example`). Laravel must be scaffolded from scratch.

---

## Definition of Done (applies to every issue)
- [ ] Code lives on a feature branch; PR opened against `main`.
- [ ] `php artisan migrate --seed` runs clean from a **fresh clone** on MySQL 8.
- [ ] `php artisan test` is green (CI on GitHub Actions must pass).
- [ ] Commit author is OpenClaw; **merged only by Yatharth** after human review.
- [ ] Multi-tenancy rule: the tenant is derived **from the authenticated session only** — never from a client-supplied `organization_id`.

---

## Issues

### #1 — Scaffold Laravel 11 API in `/backend`  *(BLOCKER — does everything else)*
**Owner:** OpenClaw
Replace the empty `/backend` with a fresh Laravel 11 application (API-only, no Blade views).
- PHP 8.2, Laravel 11. Keep `.env.example` DB block (`mysql` / `pulsedesk`, `SANCTUM_STATEFUL_DOMAINS=127.0.0.1:5173`).
- Install packages: `laravel/sanctum`, `pestphp/pest`, `pestphp/pest-plugin-laravel`.
- Wire **Pest** as the test runner (replace the default PHPUnit example test with a Pest one).
- Publish `config/sanctum.php`.
- Clear the default `2014_..._users` / `password_reset` migrations so our own schema (issue #2) is authoritative.
- Create `routes/api.php` with a smoke endpoint `GET /api/health` → `{ "status": "ok" }`.
- Ensure `/backend/vendor` and `/backend/.env` stay git-ignored (already in `.gitignore`).

**Acceptance criteria:**
- `composer install` succeeds; `php artisan serve` boots without error.
- `GET /api/health` returns 200 JSON.
- `php artisan test` runs and reports **0 failures**.

---

### #2 — Core migrations + Eloquent models  ✅ MERGED to `main` (2026-06-27)
**Owner:** OpenClaw · **Depends on:** #1
Create the five core tables with foreign keys + indexes, and their Eloquent models, per the schema in `implementation_plan.md`:

| Table | Key columns | Notes |
|---|---|---|
| `organizations` | `id`, `name`, timestamps | tenant root; **not** tenant-scoped |
| `users` | `id`, `organization_id` FK, `name`, `email`, `password`, `role` enum(`admin`,`agent`,`customer`), timestamps | unique email |
| `tickets` | `id`, `organization_id` FK, `subject`, `description`, `status` enum(`open`,`pending`,`resolved`,`closed`), `priority` enum(`low`,`medium`,`high`,`urgent`), `requester_id` FK→users, `assignee_id` FK→users nullable, timestamps | index on `(organization_id, status)` |
| `comments` | `id`, `ticket_id` FK, `user_id` FK, `body`, `is_internal` bool default false, timestamps | |
| `sla_policies` | `id`, `organization_id` FK, `priority` enum, `response_time_hours` int, `resolution_time_hours` int, timestamps | unique `(organization_id, priority)` |

- Models: `Organization`, `User`, `Ticket`, `Comment`, `SlaPolicy` with correct relationships (`hasMany` / `belongsTo`).
- `$fillable` set everywhere; enums as class constants.

**Acceptance criteria:**
- `php artisan migrate` and `php artisan migrate:fresh` run clean on MySQL 8.

---

### #3 — `TenantScope` global query scope  *(CORE SECURITY)* — ✅ MERGED to `main` (2026-06-27)
**Owner:** OpenClaw · **Depends on:** #2
Hard multi-tenant isolation, server-enforced.

> **Design deviation (resolved):** Issue #2 shipped `comments` without an `organization_id` column. OpenClaw initially took a transitive-isolation approach; this was corrected in Issue #4's PR — `Comment` now uses `TenantOwned` with a dedicated `add_organization_id_to_comments` migration. ✅ Resolved.
- `app/TenantScope.php`: a global Eloquent scope that appends `WHERE organization_id = Auth::user()->organization_id`.
- `app/Traits/TenantOwned.php` trait: boots the scope on any model that uses it **and** auto-fills `organization_id` from the authenticated user on `create`.
- Apply the trait to `User`, `Ticket`, `Comment`, `SlaPolicies` — every tenant-owned model. `Organization` is **not** scoped.
- Tenant context is read **only** from the authenticated session. No route param, query string, or request body may set the org.
- The scope must respect an "unauthenticated / cli" context (e.g. allow seeders and artisan tinker to run without an auth user) without leaking data.

**Acceptance criteria:**
- In `tinker`, an authenticated Org-A user's `Ticket::all()` returns **only** Org-A tickets.
- Creating a model as an Org-A user stamps `organization_id = A` automatically.

---

### #4 — Sanctum auth + user roles — ✅ MERGED to `main` (2026-06-27)
> **Minor gaps carried forward** (not blockers, but must be fixed in #5 or #6): (1) `config/sanctum.php` not published — Sanctum runs on defaults, acceptable but should be published. (2) `User` model missing `ROLE_ADMIN`/`ROLE_AGENT`/`ROLE_CUSTOMER` constants + `hasRole()` helper — needed by #5 seeder and #6 tests. (3) `/me` returns user only, not user + organization.
**Owner:** OpenClaw · **Depends on:** #2
- Configure Laravel Sanctum **token** authentication (stateless API tokens, not cookie sessions).
- `User::role` cast as enum; gate/policy helpers for `admin`, `agent`, `customer`.
- Auth routes in `routes/api.php`:
  - `POST /api/register` — create user (assigns to request or default org)
  - `POST /api/login` — issue Sanctum token
  - `POST /api/logout` — revoke token
  - `GET /api/me` — return authenticated user + org

**Acceptance criteria:**
- `POST /api/login` with valid creds returns a token.
- `GET /api/me` returns 200 with token, 401 without.

---

### #5 — Database seeder — 🔵 ASSIGNED to OpenClaw (2026-06-27)
**Owner:** OpenClaw · **Depends on:** #2, #4
`DatabaseSeeder` that produces exactly:
- 1 organization: **Acme**
- 1 admin: `admin@acme.test`
- 2 agents: `agent1@acme.test`, `agent2@acme.test`
- 2 customers: `customer1@acme.test`, `customer2@acme.test`
- ~12 tickets spread across `status`/`priority`, assigned to the above users, with a few sample comments (incl. one `is_internal` note).
- All passwords = `password` (hashed via `Hash::make`).

**Acceptance criteria:**
- `php artisan migrate --seed` from a fresh DB produces exactly the above counts.

---

### #6 — Sprint 1 verification tests (Pest) — keeps CI green
**Owner:** OpenClaw · **Depends on:** #3, #4, #5
A focused Pest feature test suite (the heavy cross-tenant + policy suite is **Sprint 2**):
- `GET /api/health` → 200.
- `POST /api/login` returns a token; `GET /api/me` works with it.
- **Tenant isolation smoke test:** an Org-A user `GET`s/queries and cannot reach an Org-B ticket (403/404, never the record).
- Role enum present on users.

**Acceptance criteria:**
- `php artisan test` green locally and on GitHub Actions.

---

## Outcome *(fill at sprint close)*
- Shipped: …
- Slipped / moved to next sprint: …
- PRs: #… (merged by Yatharth)
