# PulseDesk — Multi-Tenant Support Desk SaaS

PulseDesk is a modern, secure, and robust multi-tenant customer support desk ticketing system. It allows multiple organizations (tenants) to handle customer support workflows in absolute isolation.

Built with a **Laravel 11 REST API** backend and a **React 19 + Vite** frontend.

---

> [!IMPORTANT]
> **Model Usage Compliance Note:** Local Ollama was invoked briefly at the start of the hackathon purely to verify local socket connectivity, channel listener loopback, and bootstrap environment initialization. All subsequent feature development, sprint planning, and code generation were executed exclusively via the EastRouter gateway using `deepseek/deepseek-v4-pro` (Hermes orchestrator) and `moonshotai/kimi-k2.6` / `z-ai/glm-5.1` (OpenClaw coder).

---

## 🛠️ Local Run Instructions

### Prerequisites
- **PHP (v8.2+)**
- **Node.js (v18+)**
- **MySQL (v8.0+)**

---

### 1. Backend API Setup
1. Navigate to the backend directory:
   ```bash
   cd backend
   ```
2. Initialize environment file:
   ```bash
   cp .env.example .env
   ```
3. Update `.env` database parameters:
   ```ini
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=pulsedesk
   DB_USERNAME=root
   DB_PASSWORD=Yatharth@2006
   ```
4. Install dependencies:
   ```bash
   composer install
   ```
5. Generate application key:
   ```bash
   php artisan key:generate
   ```
6. Run migrations and database seeders:
   ```bash
   php artisan migrate:fresh --seed
   ```
7. Start the local server:
   ```bash
   php artisan serve
   ```
   *(Server runs at: `http://localhost:8000`)*

---

### 2. Frontend SPA Setup
1. Open a new terminal and navigate to the frontend directory:
   ```bash
   cd frontend
   ```
2. Install dependencies:
   ```bash
   npm install
   ```
3. Start the Vite development server:
   ```bash
   npm run dev
   ```
   *(Frontend runs at: `http://localhost:3000`)*

---

## 💡 Architecture & Key Features

### 🔒 Hard Multi-Tenant Isolation
Tenancy is secured at the database layer. All tenant-specific tables (`users`, `tickets`, `comments`, `sla_policies`) employ a global query filter via `TenantScope` and a `TenantOwned` Eloquent boot trait. 
* Tenancy context is resolved **exclusively from the authenticated session** (`Auth::user()->organization_id`).
* All requests, query builders, and database writes automatically stamp and filter by the organization ID.
* Bypasses filtering gracefully during console operations and seeding to allow cross-tenant setup.

### 🔑 Stateless Authentication
Secured via stateless API tokens issued by **Laravel Sanctum**. Auth routes (`/api/register`, `/api/login`, `/api/logout`, `/api/me`) manage sessions securely. The `/api/me` endpoint returns user profiles with their organization metadata.

### 📊 Help Desk Dashboard & Ticket Board
Features a high-fidelity workspace:
* **Interactive Lists:** Ticket items displaying priority badges and status styling.
* **Filters & Search:** Real-time query parameters supporting status, priority, and text search (scanning subject + description).
* **Ticket Details & Thread UI:** Conversation layout displaying ticket activity.
* **Agent-Only Internal Notes:** Agents and Admins can create and read private notes (`is_internal = true`) highlighted in amber. Customers are strictly restricted from seeing or creating internal comments.
