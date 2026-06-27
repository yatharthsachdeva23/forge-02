# Sprint 04 — Ticket Dashboard & Thread UI

**Goal:** Design and build the primary user interfaces for PulseDesk: the main ticket dashboard with search and filter parameters, the threaded message detail view with agent-only internal notes capability, and a modal for new ticket creation.

**Repo:** `github.com/yatharthsachdeva23/forge-02` (branch `main`)

---

## Definition of Done
- [ ] Dashboard displays list of tickets scoped to active tenant.
- [ ] Search input and status/priority filters are functional.
- [ ] Ticket thread detail loads and posts comments.
- [ ] Agent-only comments marked as `is_internal` are restricted based on user role.

---

## Issues

### #1 — Main Dashboard Layout & Filter Sidebar
- Build sidebar containing application header, user metadata, and Logout button.
- Build main area top-bar containing Search query inputs, Status selection filters, and Priority selection filters.
- List tickets in a grid using color-coded status badges.

### #2 — Thread Conversation UI & Internal Notes
- Click to load ticket detail container.
- Fetch comments corresponding to the active ticket ID.
- Hide internal notes (`is_internal = true`) if the logged-in user is a customer. Show them highlighted in amber if logged in as agent/admin.
- Show "Internal Note" toggle checkbox on comment creation only if user is agent/admin.

### #3 — Ticket Creation Modal
- Add Floating/Header button to open a ticket creation model dialog.
- Validate parameters and post JSON request to `/api/tickets`.

---

## Outcome
- Shipped: Main dashboard layout, ticket listings with live status/priority filters and search inputs, detail panels showing ticket details and comments threads, role-based internal comment restrictions, and ticket creation modal.
- Slipped / moved to next sprint: None.
- PRs: PR #8 (merged by Yatharth).
