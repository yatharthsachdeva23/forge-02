# Walkthrough - Collaborative Kanban Board (V2)

The Collaborative Kanban Board project has been fully audited, fixed, and verified both locally and in production. The codebase adheres strictly to the decoupled architecture requirements, with security measures and robust error handling in place.

---

## 🏗️ Decoupled Monorepo Architecture

The repository is organized into distinct directories to bypass Vercel MIME-type conflicts and ensure clean modular deployment:

```
/ (root)
├── backend/                # Laravel SQLite API
├── frontend/               # React + Vite + Tailwind CSS SPA
├── config/                 # Redacted configurations (openclaw.json, hermes.json)
├── screenshots/            # Comitted verification images
├── vercel.json             # Vercel deployment & routing config
├── README.md               # User setup & run commands
├── ARCHITECTURE.md         # Full design documentation
└── agent-log.md            # Collaborative execution log
```

---

## 💻 Frontend Audit & Features (`/frontend`)

*   **Vite + React Scaffolding:** Initialized cleanly using the latest React + Vite standard template.
*   **Tailwind CSS v4 Integration:** Fully configured using `@tailwindcss/postcss` and `@import "tailwindcss";` in `src/index.css`.
*   **Drag-and-Drop Interactivity:** Integrated `@hello-pangea/dnd` for smooth, responsive card dragging and snapping.
*   **Precision Index-Based Reordering:** Reordered cards are inserted exactly at their drop position index (`destIndex`) using `splice` instead of always appending to the end.
*   **Optimistic UI & Rollback:** 
    *   Cards move instantly upon drag-end in local state.
    *   An asynchronous `PATCH` sync request is dispatched to the backend.
    *   If the network fails or is offline, the state instantly reverts to a deep snapshot, snapping the card back to its origin.
*   **API-First with Mock Fallback:**
    *   Connects to the live API base URL: `https://forge-qualifier-production.up.railway.app/api`.
    *   If the backend is unreachable or times out, it logs `Backend unreachable. Switching to mock data mode.` and falls back to `mockData.json` data.
*   **Visual Indicators:**
    *   **Overdue Badges:** Prominent red warning badges for cards with past due dates that are not in the "Done" column.
    *   **Tags:** Styled tags based on type (Design $\rightarrow$ Blue, Bug $\rightarrow$ Red, Feature $\rightarrow$ Green, Other $\rightarrow$ Gray).
    *   **Avatars:** Dynamically generated unique avatars via the DiceBear API based on `member_id`.

---

## 🛠️ Backend Audit & Deployment (`/backend`)

*   **Laravel API:** Handled routing inside `routes/api.php` and registered it correctly in `bootstrap/app.php`.
*   **SQLite Database:** Located at `database/database.sqlite`. Pre-seeded with 4 cards to demonstrate tags, overdue styling, columns, and avatars.
*   **Railway Hosting:** Deployed on Railway at: `https://forge-qualifier-production.up.railway.app`.
    *   `APP_KEY` set to a valid 32-byte AES key.
    *   `DB_DATABASE` env variable deleted to allow Laravel to resolve the absolute database path dynamically.

---

## 🚀 Production Verification Status

*   **Vercel Frontend Build:** Deployed successfully at `https://forge-qualifier.vercel.app`.
*   **Railway Backend API:** Active, routing requests, and writing to the SQLite database.
*   **End-to-End Connectivity:** Verified. Adding a card from Vercel makes a POST request to Railway, writes to the DB, and persists on page reload.
