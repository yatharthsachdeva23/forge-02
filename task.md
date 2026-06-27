# PulseDesk Sprint Checklist

- `[ ]` **Sprint 1: Backend Foundation & Scaffolding**
  - `[ ]` Initialize Laravel 11 under `/backend`
  - `[ ]` Create database migrations (`organizations`, `users`, `tickets`, `comments`, `sla_policies`)
  - `[ ]` Implement `TenantScope` global query filter for multi-tenant isolation
  - `[ ]` Configure Sanctum Auth and user role types (`admin`, `agent`, `customer`)
  - `[ ]` Setup seeders with 1 organization, 1 admin, 2 agents, 2 customers, and 12 tickets

- `[ ]` **Sprint 2: API Endpoints & Multi-Tenancy Pest Tests**
  - `[ ]` Create authenticated REST endpoints in `routes/api.php`
  - `[ ]` Implement `TicketController` with filter and text search logic
  - `[ ]` Implement `CommentController` with private note visibility checks
  - `[ ]` Write Pest/PHPUnit tests to verify cross-tenant data isolation and authorization policies
  - `[ ]` Configure `.github/workflows/ci.yml` for automated PR checking

- `[ ]` **Sprint 3: React Setup & Auth Client UI**
  - `[ ]` Initialize React SPA with Vite under `/frontend`
  - `[ ]` Configure Tailwind CSS styling
  - `[ ]` Implement frontend API client layer with auth token header inclusion
  - `[ ]` Create login/register user flows and token storage mechanisms

- `[ ]` **Sprint 4: Ticket Dashboard & Thread UI**
  - `[ ]` Create dashboard container, layout grid, and side-nav
  - `[ ]` Build ticket board and detail sidebars with filter bars
  - `[ ]` Implement ticket creation panel/modal
  - `[ ]` Build threaded message history component showing internal/agent-only logs
  - `[ ]` Add avatar integration (DiceBear avatar links)

- `[ ]` **Sprint 5: SLA Trackers & Dashboard Analytics (Should/Stretch)**
  - `[ ]` Code backend SLA status calculations and metrics API
  - `[ ]` Build dynamic dashboard cards (Open, Pending, Avg first-response, SLA breaches)
  - `[ ]` Implement dynamic SLA timers on frontend ticket views
  - `[ ]` Add ticket claim queue flows
