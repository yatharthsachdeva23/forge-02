# Sprint 02 — API Endpoints & Multi-Tenancy Security Verification

**Goal:** Implement all core API endpoints for managing tickets and comments, and write feature tests to verify cross-tenant data isolation and role-based policies.

**Repo:** `github.com/yatharthsachdeva23/forge-02` (branch `main`)

---

## Definition of Done
- [ ] Code lives on a feature branch; PR opened against `main`.
- [ ] `php artisan test` is green locally.
- [ ] Multi-tenancy rule: the tenant is derived **from the authenticated session only**.
- [ ] Role-based access rules strictly enforced.

---

## Issues

### #1 — Ticket API Endpoints (CRUD + Filters + Search)
**Owner:** OpenClaw
- Create `TicketController` under `app/Http/Controllers/Api/`.
- Endpoints:
  - `GET /api/tickets` — Index endpoint. Returns only tickets belonging to the authenticated user's organization. Add support for filtering by: `status`, `priority`, and text search (searches `subject` and `description`).
  - `POST /api/tickets` — Store endpoint. Creates a new ticket. Automatically sets the `requester_id` to `Auth::user()->id`. Tenant Scope stamps the `organization_id`.
  - `GET /api/tickets/{id}` — Show endpoint. Returns a single ticket.
  - `PUT /api/tickets/{id}` — Update endpoint. Allows updating `status`, `priority`, and `assignee_id`.
  - `POST /api/tickets/{id}/assign` — Assignment endpoint. Allows agents/admins to claim a ticket or assign it to another user.
- Add route mappings in `routes/api.php` under the `auth:sanctum` group.

**Acceptance criteria:**
- CRUD actions only return and modify data for the active user's organization.
- Searching and filtering yields correct scoped matches.

---

### #2 — Comment API Endpoints (Threaded Notes + Visibility Gates)
**Owner:** OpenClaw
- Create `CommentController` under `app/Http/Controllers/Api/`.
- Endpoints:
  - `GET /api/tickets/{ticket_id}/comments` — Index endpoint. If user has the `customer` role, do not return any comment where `is_internal = true` (internal agent notes). Agents and admins see all comments.
  - `POST /api/tickets/{ticket_id}/comments` — Store endpoint. Creates a comment, automatically setting `user_id` to `Auth::user()->id`. Customers cannot set `is_internal` to `true`.
- Add routes mapped to `/api/tickets/{ticket}/comments` under the `auth:sanctum` group.

**Acceptance criteria:**
- Customer role users can never fetch or post comments marked as internal (`is_internal = true`).

---

### #3 — Pest Security & Verification Test Suite
**Owner:** OpenClaw
- Create feature tests in `tests/Feature/TicketApiTest.php` and `tests/Feature/CommentApiTest.php`.
- Test cases must cover:
  - CRUD access restrictions (Org-A user cannot view/edit Org-B tickets).
  - Search and filter logic on the ticket index.
  - Comment visibility bounds (customer cannot fetch `is_internal = true` comments, customer cannot create `is_internal = true` comments).
  - Validation rules for login, register, and token revocation.

**Acceptance criteria:**
- `php artisan test` runs and reports 100% green runs.

---

## Outcome
- Shipped: ...
- Slipped / moved to next sprint: ...
- PRs: ...
