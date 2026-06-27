# Architecture Document — PulseDesk

PulseDesk utilizes a decoupled client-server architecture to enforce clear boundaries between data persistence, business logic, and UI representation.

---

## 📂 Repository Layout
- **Backend API:** `/backend` (PHP 8.3 / Laravel 11 / MySQL)
- **Frontend SPA:** `/frontend` (React 19 / Vite / Tailwind CSS 4)

---

## ⚙️ Backend Architecture

### 1. Database Schema
MySQL 8.0 is structured across 5 core tables:
* `organizations`: The tenant roots containing organization names.
* `users`: Team members containing email, role (admin/agent/customer), and foreign organization constraints.
* `tickets`: Main tickets containing subjects, status, priority, requester_id, and assignee_id.
* `comments`: Discussion threads linked to tickets. Includes `is_internal` boolean flag for private agent notes.
* `sla_policies`: Service Level Agreement configurations mapping response/resolution thresholds per priority.

### 2. Multi-Tenant Security Layer
- **Global Scope (`TenantScope`):** Appends `WHERE organization_id = Auth::user()->organization_id` to database queries.
- **Model Trait (`TenantOwned`):** Boots the `TenantScope` and hooks into the `creating` event to automatically stamp the organization ID of the authenticated user.
- **Bypass Guard:** Deactivates queries restriction if `Auth::user()` is null, permitting database seeders and artisan console commands to run without errors.

### 3. Endpoints & Controllers
* **`AuthController`:** Manages stateless authentication via Laravel Sanctum:
  - `POST /api/register` (creates user, validates org_id)
  - `POST /api/login` (issues token)
  - `POST /api/logout` (revokes current token)
  - `GET /api/me` (returns user with organization relationship)
* **`TicketController`:** Scoped CRUD endpoints:
  - `GET /api/tickets` (index with search, status, and priority query filters)
  - `POST /api/tickets` (creates tickets, auto-stamps requester)
  - `GET /api/tickets/{id}` (shows ticket detail)
  - `PUT /api/tickets/{id}` (updates status, priority, and assignee)
  - `POST /api/tickets/{id}/assign` (claims or updates ticket assignment)
* **`CommentController`:** Discussion thread managers:
  - `GET /api/tickets/{ticket}/comments` (returns threads; hides `is_internal = true` notes if role is customer)
  - `POST /api/tickets/{ticket}/comments` (saves comment; prevents customers from setting `is_internal = true`)

---

## 🎨 Frontend Architecture

### 1. State Management & API Layer
- **Client (`src/api/client.js`):** Axios client pre-configured with interceptors to automatically append the stored Sanctum Authorization Bearer token to all outbound requests.
- **Auth Provider (`src/hooks/useAuth.jsx`):** Context provider managing session state, credentials verification, and routing redirections on 401 unauthenticated errors.

### 2. Dashboard Components
- **Sidebar:** Handles profile metadata display and user role indicators.
- **Filters Row:** Live query state bindings for ticket priority and status.
- **Detail Thread Panel:** Embeds comment timelines, dynamically styling internal agent-only notes.
- **Ticket Modal:** Handles prompt validations and ticket creation requests.
