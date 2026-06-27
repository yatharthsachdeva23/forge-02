# AI Agent Coordination Playbook (12:00 PM – 6:00 PM Timeline)

This document contains copy-pasteable prompts to run your agents (Hermes & OpenClaw) via Slack. Follow the sprints in order.

---

## 🏁 Phase 0: Setup and Seeding the Project (12:00 PM – 12:15 PM)
*Before sending prompts, make sure both gateways are running in your terminal:*
1. Run `openclaw gateway` in one terminal window.
2. Run `hermes gateway` in another terminal window.

---

## 🏃 Sprint 1: Database Scaffolding & Multi-Tenancy (12:15 PM – 01:30 PM)
* **Goal Time:** 12:15 PM – 01:15 PM (60 mins)
* **Debugging Buffer:** 01:15 PM – 01:30 PM (15 mins)

### Step 1: Tell Hermes to Plan the Sprint
*Send this prompt in the `#sprint-main` channel (your main planning channel with Hermes):*
```text
Hey Hermes. We are building PulseDesk, a multi-tenant support-desk SaaS.
Our required stack is: Laravel 11 API (backend), React 19 + Vite (frontend), MySQL 8 database, Pest/PHPUnit feature tests, and GitHub Actions CI.

Please plan and write Sprint 1. The goal is to set up the backend base and database tables with strong multi-tenant security isolation. Specifically:
- Create the main migrations: organizations, users, tickets, comments, and sla_policies.
- Set up a TenantScope query scope in Laravel so all queries are automatically scoped by organization_id based on the authenticated user.
- Configure Laravel Sanctum auth and define the user roles: admin, agent, customer.
- Create a seeder populated with: 1 organization, 1 admin, 2 agents, 2 customers, and ~12 sample tickets.

Write the sprint backlog plan to `sprints/sprint-01.md`. Once written, post the backlog here and assign the first task to OpenClaw.
```

### Step 2: OpenClaw Executes Sprint 1
*Hermes should automatically assign this task to OpenClaw in `#agent-coder`. If you want to trigger it manually, send this in `#agent-coder`:*
```text
OpenClaw, please implement Sprint 1.
Create the backend Laravel 11 app under the `/backend` folder. Build the models and database migrations for organizations, users, tickets, comments, and sla_policies. Apply the multi-tenancy TenantScope global query filter. Set up Sanctum auth, roles, and populate DatabaseSeeder.
When done, run the migrations and seeds, run a basic test, open a PR to main (do not auto-merge), and write your status report to #agent-log.
```

---

## 🏃 Sprint 2: Core API Endpoints & Pest Isolation Tests (01:30 PM – 02:45 PM)
* **Goal Time:** 01:30 PM – 02:30 PM (60 mins)
* **Debugging Buffer:** 02:30 PM – 02:45 PM (15 mins)

### Step 1: Instruct Hermes to Plan Sprint 2
*Send this in `#sprint-main`:*
```text
Hermes, Sprint 1 is merged successfully. Now let's plan Sprint 2.
The goal of Sprint 2 is to code the REST API endpoints and write Pest feature tests to verify absolute tenant isolation and role restrictions.
Specifically:
- Build `TicketController` under `backend/app/Http/Controllers/Api` with full CRUD, search, and filtering capabilities.
- Build `CommentController` to manage threaded replies, ensuring the `is_internal` flag is protected so customers cannot see agent-only notes.
- Write Pest/PHPUnit feature tests confirming that:
  - Users from Organization A cannot view, edit, or delete tickets belonging to Organization B (cross-tenant safety check).
  - Customer roles cannot perform agent actions (such as claiming or reassigning tickets).
- Create a `.github/workflows/ci.yml` that performs Composer installation, runs migrations, and executes Pest tests on every pull request.

Please update `sprints/sprint-02.md` with the backlog, post it here, and assign the implementation task to OpenClaw in #agent-coder.
```

---

## 🏃 Sprint 3: React Frontend Auth & Setup (02:45 PM – 04:00 PM)
* **Goal Time:** 02:45 PM – 03:45 PM (60 mins)
* **Debugging Buffer:** 03:45 PM – 04:00 PM (15 mins)

### Step 1: Instruct Hermes to Plan Sprint 3
*Send this in `#sprint-main`:*
```text
Hermes, Sprint 2 API and Pest tests are merged. Let's plan Sprint 3: Client Authentication.
The goal is to scaffold the frontend React application and connect it to the Laravel Sanctum backend.
Specifically:
- Initialize a React 19 + Vite app inside the `/frontend` directory.
- Configure Tailwind CSS.
- Build a central API client (using fetch or axios) that automatically includes the bearer token in auth headers.
- Build Register, Login, and Logout forms that communicate with the backend Sanctum routes and persist the token.

Please write the backlog to `sprints/sprint-03.md` and instruct OpenClaw to begin.
```

---

## 🏃 Sprint 4: Ticket Dashboard & Threaded Conversation UI (04:00 PM – 05:15 PM)
* **Goal Time:** 04:00 PM – 05:00 PM (60 mins)
* **Debugging Buffer:** 05:00 PM – 05:15 PM (15 mins)

### Step 1: Instruct Hermes to Plan Sprint 4
*Send this in `#sprint-main`:*
```text
Hermes, frontend auth is complete. Let's plan Sprint 4: The Ticket Dashboard and Conversational UI.
The goal is to build the core interface of PulseDesk.
Specifically:
- Create a responsive main dashboard with a sidebar and ticket queue.
- Support status/priority/assignee filtering and text search.
- Create a "Create Ticket" modal.
- Create a Ticket Detail page featuring the conversation log. Visual styles should clearly distinguish between public customer replies and agent-only private notes.
- Integrate DiceBear avatar URLs to render avatars for assignees.

Update `sprints/sprint-04.md` and direct OpenClaw.
```
