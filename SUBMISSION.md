# Submission Checklist

## 🏗️ Architecture & Layout
- [x] Decoupled structure (`/backend`, `/frontend`)
- [x] Root `vercel.json` configured for frontend routing
- [x] SQLite database used in `/backend`

## ⚙️ Backend API
- [x] Three-tier hierarchy: Board $\rightarrow$ List $\rightarrow$ Card
- [x] `GET /api/board` implemented
- [x] `POST /api/cards` implemented
- [x] `PATCH /api/cards/{id}` implemented
- [x] Initial seed data (To-Do, Doing, Done)

## 🎨 Frontend UI
- [x] Vite + React + Tailwind CSS
- [x] High-fidelity cards with dynamic tags (colors)
- [x] Member avatars rendered
- [x] "OVERDUE" badge logic (date comparison + non-Done column)
- [x] Responsive side-by-side grid layout

## ⚡ Interactivity
- [x] Drag-and-Drop movement between columns
- [x] Optimistic UI updates for movement (Instant snap)
- [x] Graceful fallback to `mockData.json` when backend is offline
- [x] "Add Card" functionality with immediate UI update

## 🛡️ Security & Documentation
- [x] `openclaw.json` and `hermes.json` redacted in `/config`
- [x] Updated `README.md` and `ARCHITECTURE.md`
- [x] Complete `agent-log.md` transcript
- [x] Screenshots in `/screenshots` folder
