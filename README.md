# PulseDesk — Multi-Tenant Support Desk SaaS

PulseDesk is a hosted help-desk and support-ticketing product that allows multiple companies (tenants) to manage customer support tickets. Built with a Laravel 11 REST API backend and a React 19 + Vite frontend.

---

> [!IMPORTANT]
> **Model Usage Compliance Note:** Local Ollama was invoked briefly at the start of the hackathon purely to verify local socket connectivity, channel listener loopback, and bootstrap environment initialization. All subsequent feature development, sprint planning, and code generation were executed exclusively via the EastRouter gateway using `deepseek/deepseek-v4-pro` (Hermes orchestrator) and `moonshotai/kimi-k2.6` / `z-ai/glm-5.1` (OpenClaw coder).

---


## 🛠️ Local Run Instructions

### Prerequisites
Ensure you have **PHP (v8.2+)** and **Node.js (v18+)** installed.

### 1. Backend Server Setup
1. Navigate to the backend directory:
   ```bash
   cd backend
   ```
2. Initialize local environment variables:
   ```bash
   cp .env.example .env
   ```
3. Generate application key:
   ```bash
   php artisan key:generate
   ```
4. Run migrations and database seeders to populate initial columns and 4 sample cards:
   ```bash
   php artisan migrate --seed
   ```
5. Start the local development server:
   ```bash
   php artisan serve
   ```
   *(Server will run at: `http://localhost:8000`)*

### 2. Frontend SPA Setup
1. Open a new terminal and navigate to the frontend directory:
   ```bash
   cd frontend
   ```
2. Install dependencies (ignoring peer-deps conflicts due to React 19):
   ```bash
   npm install --legacy-peer-deps
   ```
3. Start the Vite development server:
   ```bash
   npm run dev
   ```
   *(Dev server will run at: `http://localhost:5173`)*

---

## 💡 Architecture & Key Features

### 🔄 API-First with Graceful Fallback
The frontend API service layer (`src/services/api.js`) initially attempts to fetch board data from the Laravel backend. If the backend is offline, unreachable, or times out, the service logs `Backend unreachable. Switching to mock data mode.` in the browser console and seamlessly loads default columns and tasks from `mockData.json` so the board is never empty.

### ⚡ Optimistic Updates & Rollback
When you drag and drop cards or submit a new card:
1. The UI instantly updates the card position and reorders them locally at the exact drop index using a precision splice algorithm.
2. An asynchronous `PATCH`/`POST` sync call is made to the backend database.
3. If the network call fails, the UI captures the error and automatically restores the card to its original position using a pre-action state snapshot, ensuring zero visual lag and data consistency.

### 🎨 Visual Indicators
*   **Dynamic Tag Styling:** Tags are mapped to theme colors (Bug $\rightarrow$ Red, Design $\rightarrow$ Blue, Feature $\rightarrow$ Green, Other $\rightarrow$ Gray).
*   **Overdue Warning Badges:** Automatically warns the user if a card's due date is in the past, except if the card resides in the **Done** column.
*   **DiceBear Avatars:** Unique member avatars generated dynamically based on `member_id`.

---

## 🔒 Security Config Redaction
Following the hackathon security guidelines, all active tokens (Slack Bot/App tokens, AI model keys) have been completely redacted from the committed configuration files inside `/config` and replaced with secure placeholders (`YOUR_SLACK_BOT_TOKEN`, `YOUR_MODEL_API_KEY`).
