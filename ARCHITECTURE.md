# Architecture Document

## System Design
This project implements a **decoupled client-server architecture** to ensure deployment stability and scalability.

### 1. Repository Layout
The project is strictly partitioned to prevent Vercel deployment conflicts:
- **Frontend Root:** `/frontend` (Deployed to Vercel)
- **Backend Root:** `/backend` (Isolated API)
- **Root Config:** `vercel.json` routes all requests to the `/frontend` directory.

### 2. Backend (Laravel + SQLite)
- **Engine:** PHP 8.3 / Laravel 11
- **Database:** SQLite (File-based)
- **Schema:** Three-tier hierarchy: `Board` $\rightarrow$ `KanbanList` $\rightarrow$ `Card`.
- **Endpoints:**
  - `GET /api/board`: Fetches full hierarchy.
  - `POST /api/cards`: Creates new cards.
  - `PATCH /api/cards/{id}`: Updates card details or column position.

### 3. Frontend (React + Vite + Tailwind)
- **UI Logic:** Interactive Kanban board with optimistic state updates.
- **Fallback Mechanism:** 
  - The app first attempts to reach the Laravel API.
  - If the backend is offline, the `apiService` catches the error, logs a warning, and seamlessly switches to `mockData.json` to ensure the board remains interactive.
- **Key Features:** 
  - Drag-and-Drop via `@hello-pangea/dnd`.
  - "Overdue" badge based on local date vs card due date.
  - Color-coded tags (Bug, Design, Feature, Other).
