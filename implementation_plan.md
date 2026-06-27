# PulseDesk Implementation Plan

PulseDesk is a multi-tenant support-desk SaaS. This document outlines the technical architecture, database schema, API design, and the sprint workflow.

---

## 🏗️ Technical Architecture

### 1. Backend: Laravel 11 API
* **Auth**: Laravel Sanctum for API token issuance and stateless authentication.
* **Multi-tenancy**: Shared-database strategy. Every tenant table has an `organization_id` column.
  * **Global Scope**: A `TenantScope` query scope will automatically append `WHERE organization_id = ?` to all model queries.
  * **Tenant Context**: Set dynamically during authentication middleware via `Auth::user()->organization_id`.
* **Database**: MySQL 8.
* **Roles & Permissions**: Custom policies restricting ticket access based on role (`admin`, `agent`, `customer`).

### 2. Frontend: React 19 + Vite + Tailwind CSS
* **Routing**: React Router or clean state-based view switching.
* **API Client**: Axios or Fetch with requests pointing to `/api` and passing the Sanctum token in headers.
* **UI/UX**: Clean dashboard grid with a sidebar, ticket queue filterable by status/priority, and live SLA timers.

---

## 🗄️ Database Schema

### 1. `organizations` (Tenants)
* `id` (bigint, PK, auto_increment)
* `name` (varchar)
* `timestamps`

### 2. `users`
* `id` (bigint, PK, auto_increment)
* `organization_id` (bigint, FK organizations.id)
* `name` (varchar)
* `email` (varchar, unique within tenant or global)
* `password` (varchar)
* `role` (enum: `'admin'`, `'agent'`, `'customer'`)
* `timestamps`

### 3. `tickets`
* `id` (bigint, PK, auto_increment)
* `organization_id` (bigint, FK organizations.id)
* `subject` (varchar)
* `description` (text)
* `status` (enum: `'open'`, `'pending'`, `'resolved'`, `'closed'`)
* `priority` (enum: `'low'`, `'medium'`, `'high'`, `'urgent'`)
* `requester_id` (bigint, FK users.id)
* `assignee_id` (bigint, FK users.id, nullable)
* `timestamps`

### 4. `comments` (Threaded Replies)
* `id` (bigint, PK, auto_increment)
* `ticket_id` (bigint, FK tickets.id)
* `user_id` (bigint, FK users.id)
* `body` (text)
* `is_internal` (boolean, default false) - internal notes visible to agents only
* `timestamps`

### 5. `sla_policies` (Should Tier)
* `id` (bigint, PK, auto_increment)
* `organization_id` (bigint, FK organizations.id)
* `priority` (enum: `'low'`, `'medium'`, `'high'`, `'urgent'`)
* `response_time_hours` (int)
* `resolution_time_hours` (int)
* `timestamps`

---

## 🏃 Sprint Breakdowns

### Sprint 1: Backend Foundation & Multi-Tenancy Scaffold
* **Goal**: Establish the MySQL database, Laravel 11 API scaffolding, multi-tenancy global scopes, and pre-populate the DB.
* **Steps**:
  1. Initialize the Laravel 11 app in `/backend`.
  2. Create migrations for `organizations`, `users`, `tickets`, `comments`, and `sla_policies` with foreign keys.
  3. Create `TenantScope` global scope and apply to all tenant models.
  4. Scaffold Sanctum authentication.
  5. Build `DatabaseSeeder` to create 1 organization, 1 admin, 2 agents, 2 customers, and 12 tickets.

### Sprint 2: Core Tickets API & Verification Tests
* **Goal**: Code the REST API endpoints and write Pest/PHPUnit tests to verify multi-tenant isolation.
* **Steps**:
  1. Define routes in `routes/api.php` under Sanctum auth middleware.
  2. Implement `TicketController` (CRUD actions, filter/search logic, reassignment/claiming tickets).
  3. Implement `CommentController` (creating replies; enforce that only agents/admins can post or view `is_internal` comments).
  4. Write Pest/PHPUnit feature tests validating:
     * Tenant isolation: Users from Org A cannot access/edit Org B's tickets.
     * Role authorization: Customers cannot assign tickets or see internal notes.
  5. Setup `.github/workflows/ci.yml` to run tests on PR checks.

### Sprint 3: React Frontend Authentication & Client Setup
* **Goal**: Scaffold the React SPA, install Tailwind, and implement register/login client pages.
* **Steps**:
  1. Initialize Vite React in `/frontend`.
  2. Install and configure Tailwind CSS and styling.
  3. Create an API utility file with Axios/fetch base setup (handling token headers).
  4. Code register, login, and logout UI components.

### Sprint 4: Ticket Dashboard & Threaded Conversations UI
* **Goal**: Build the main PulseDesk user interface for agents and customers.
* **Steps**:
  1. Code a responsive dashboard layout with a navigation sidebar and queue filters.
  2. Build a ticket listing panel displaying ticket subjects, statuses, priorities, and assignee avatars.
  3. Code the ticket creation modal with priority and description selectors.
  4. Build a ticket detail view with comment threads, distinguishing visual styles for agent-only internal notes.
  5. Render dynamic avatars based on user ID using DiceBear API.

### Sprint 5: SLA Indicators & Dashboard Metrics (Should/Stretch)
* **Goal**: Implement live SLA timers, ticket status metrics, and claim queues.
* **Steps**:
  1. Calculate SLA breach timestamps on the backend.
  2. Display color-coded SLA countdown timers on the ticket cards.
  3. Implement a "Claim Ticket" action button.
  4. Add summary counts (Open, Pending, Resolved) at the top of the dashboard.
