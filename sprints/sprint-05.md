# Sprint 05 — SHOULD Depth: Metrics, SLA & Activity Log

**Goal:** Close remaining MUST gaps (tags, DELETE, role guards) and land the first three SHOULD-tier features: dashboard metrics, SLA target display, and activity logging.

**Repo:** `github.com/yatharthsachdeva23/forge-02` (branch `main`)

---

## Definition of Done
- [ ] Tags/labels column added to tickets; seeded with sample tags.
- [ ] `DELETE /api/tickets/{id}` implemented with role guard (admin only).
- [ ] Role-based authorization on ticket update + assign (admin/agent only; customers cannot change status/priority or assign).
- [ ] Assignee filter added to `GET /api/tickets`.
- [ ] `GET /api/tickets/metrics` endpoint returning counts by status.
- [ ] Dashboard renders KPI counters (Open, Pending, Resolved) at top.
- [ ] SLA resolution target displayed on ticket detail view based on priority.
- [ ] `activity_logs` table created; entries written on ticket create, status/assignee change, and comment.
- [ ] All new endpoints covered by Pest tests.
- [ ] `php artisan test` green; `npm run build` clean.

---

## Issues

### #1 — MUST Patch: Tags, DELETE Route, Role Guards, Assignee Filter
> **Priority: CRITICAL.** Must land before Issues #2–#4 so the SHOULD features build on a solid MUST base.

**Backend tasks:**

1. **Tags/labels migration:**
   - Add `tags` column (`json`, nullable) to `tickets` table via new migration.
   - Add `tags` to `Ticket::$fillable`.
   - Update `TicketController::store()` and `update()` to accept optional `tags` array (validate as `array`, each value `string|max:50`).
   - Seed at least 2 tickets with tags (e.g., `["billing", "urgent"]`).

2. **DELETE route:**
   - Add `Route::delete('/tickets/{id}', [TicketController::class, 'destroy'])`.
   - `destroy()` must check `$request->user()->role === 'admin'` — return 403 for non-admins.
   - Soft-delete NOT required; hard delete is fine.

3. **Role guards on update + assign:**
   - `TicketController::update()`: only `admin` and `agent` can call. Customers get 403.
   - `TicketController::assign()`: only `admin` and `agent` can call. Customers get 403.
   - Implementation: add a private `authorizeAgentOrAdmin(Request $request): void` helper that throws 403 if `role === 'customer'`. Call at top of `update()` and `assign()`.

4. **Assignee filter:**
   - In `TicketController::index()`, add: `if ($request->filled('assignee_id')) { $query->where('assignee_id', $request->integer('assignee_id')); }`

5. **Pest tests:**
   - Customer cannot update ticket → 403.
   - Customer cannot assign ticket → 403.
   - Customer cannot delete ticket → 403.
   - Admin can delete ticket → 200/204.
   - Tags accepted on store and update.
   - Assignee filter returns only matching tickets.

---

### #2 — Activity Logs (Backend)
> Builds the audit trail that the rubric requires: who did what, when.

**Deliverables:**

1. **Migration:** `activity_logs` table:
   - `id` (bigIncrements)
   - `ticket_id` (foreignId, cascades on delete)
   - `user_id` (foreignId, nullable — system actions may have no user)
   - `action_description` (string, max 255)
   - `timestamps`

2. **Model:** `App\Models\ActivityLog`
   - `use TenantOwned;`
   - `$fillable`: `organization_id`, `ticket_id`, `user_id`, `action_description`
   - Relationships: `ticket()` → BelongsTo, `user()` → BelongsTo

3. **Write entries on these events:**
   - **Ticket created:** `"Ticket created"` — in `TicketController::store()` after create.
   - **Status changed:** `"Status changed from {old} to {new}"` — in `TicketController::update()` when status differs.
   - **Assignee changed:** `"Assignee changed from {old} to {new}"` — in `update()` or `assign()`.
   - **Comment added:** `"Comment added"` — in `CommentController::store()`.

4. **Endpoint:** `GET /api/tickets/{id}/activity`
   - Returns all `ActivityLog` entries for the ticket, ordered by `created_at DESC`, with user name eager-loaded.
   - Tenant-scoped automatically via `TenantOwned`.

5. **Pest tests:**
   - Creating a ticket writes an activity log entry.
   - Changing status writes an entry with old/new values.
   - Adding a comment writes an entry.
   - `GET /tickets/{id}/activity` returns entries scoped to tenant.

---

### #3 — Dashboard Metrics (Backend + Frontend)
> KPI counters at the top of the dashboard.

**Backend:**

1. **Endpoint:** `GET /api/tickets/metrics`
   - Returns JSON: `{ "open": N, "pending": N, "resolved": N, "closed": N, "total": N }`
   - Query: `Ticket::query()->selectRaw('status, count(*) as count')->groupBy('status')->pluck('count', 'status')`
   - Tenant-scoped automatically.

2. **Route placement:** Must be BEFORE `/tickets/{id}` in `routes/api.php` to avoid route conflict (Laravel would match `metrics` as `{id}`).

3. **Pest test:** Assert correct counts for a known seeded set.

**Frontend:**

4. **Dashboard.jsx:** Add a metrics row at the top of the main area (above the filter bar):
   - Fetch from `/api/tickets/metrics` on mount.
   - Render 4 stat cards: Open (green), Pending (amber), Resolved (blue), Closed (slate).
   - Each card: large number + label, Tailwind card styling.
   - Refetch when tickets are created/updated (pass `fetchMetrics` to `CreateModal`'s `onCreated` callback).

---

### #4 — SLA Target Display (Backend + Frontend)
> Show resolution SLA target on ticket detail based on priority.

**Backend:**

1. **SlaPolicy is already seeded** (4 policies: low/medium/high/urgent with resolution_time_hours).
2. **Endpoint addition:** Modify `TicketController::show()` to eager-load the SLA policy:
   ```php
   $ticket = Ticket::with('requester', 'assignee')->findOrFail($id);
   $sla = SlaPolicy::where('organization_id', $ticket->organization_id)
       ->where('priority', $ticket->priority)
       ->first();
   return response()->json([...$ticket->toArray(), 'sla' => $sla]);
   ```
   Or add a `sla()` relationship on Ticket keyed by priority.

3. **Pest test:** `GET /tickets/{id}` response includes `sla` object with `resolution_time_hours`.

**Frontend:**

4. **TicketDetail.jsx:** Below the status badge, render:
   ```
   SLA: {resolution_time_hours}h Resolution Target
   ```
   - Style as a small badge/tooltip.
   - Color-code: urgent → red, high → orange, medium → blue, low → slate.
   - If no SLA policy found, show "No SLA target".

---

## Sprint Flow

```
Issue #1 (MUST patch) → merge → Issue #2 (Activity logs) → merge → Issue #3 (Metrics) + Issue #4 (SLA) can run in parallel
```

Issues #1 and #2 are sequential (both touch TicketController).
Issues #3 and #4 can be combined into a single PR or done sequentially after #2.

---

## Outcome
- Shipped: _(to be filled after merge)_
- Slipped / moved to next sprint: _(to be filled)_
- PRs: _(to be filled)_
