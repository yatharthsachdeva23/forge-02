# Sprint 03 — React Setup & Auth Client UI

**Goal:** Scaffold a Vite-based React single-page application under `/frontend`, set up styling via Tailwind CSS, and build client-side authentication screens (register/login) integrated with our backend Sanctum token endpoints.

**Repo:** `github.com/yatharthsachdeva23/forge-02` (branch `main`)

---

## Definition of Done
- [ ] SPA initialized and builds on Vite.
- [ ] Authentication pages connect with `/api/register` and `/api/login`.
- [ ] Access tokens stored securely in localStorage.
- [ ] Route guarding implemented to prevent unauthenticated access.

---

## Issues

### #1 — Scaffolding and Tailwind Configuration
- Initialize Vite + React inside `/frontend`.
- Install packages: `tailwindcss`, `axios`, `lucide-react`, `react-router-dom`.
- Configure environment file pointing to local Laravel API URL.

### #2 — Authentication Screens & Token Storage
- Build a Register card enabling user sign-up matching the backend parameters.
- Build a Login card handling email/password inputs.
- Automatically save generated Sanctum plainTextToken inside client localStorage.
- Add an interceptor to Axios requests to inject the Sanctum token automatically.

---

## Outcome
- Shipped: Vite React SPA initialized, Tailwind config applied, client-side routing, register/login pages with localStorage authentication token persistence and route protection.
- Slipped / moved to next sprint: None.
- PRs: PR #8 (merged by Yatharth).
