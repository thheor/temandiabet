# Implementation Plan: Teman Diabet MVP (`/temandiabet`)

Source spec: PRD Final MVP (Teman Diabet — User & Inner Circle)

## Overview

We are building **Teman Diabet**, a group-developed diabetes self-management and family monitoring web application adapted from _Teman Diabetes_. The system pairs a **React (TypeScript) + Tailwind CSS** SPA with a **PHP Native + MySQLi** JSON REST API and a **5-table MySQL** database (`users`, `blood_glucose_records`, `food_logs`, `medication_schedules`, `inner_circles`) using English identifiers. Every team member's environment shares the canonical `/temandiabet` root path so both PHP built-in server (`php -S`) and Apache/XAMPP (`htdocs/temandiabet`) resolve routes, assets, uploads, and session cookies identically. Production is served at the domain root with no prefix, so nothing is hardcoded to the dev prefix.

## Architecture Decisions

- **Canonical `/temandiabet` prefix (development only):**
  - **XAMPP teammates** place the repo at `htdocs/temandiabet/`, serving the backend at `http://localhost/temandiabet/backend/api/...` and optional built frontend at `http://localhost/temandiabet/`.
  - **PHP Native (`php -S`) developer** runs `php -S localhost:8000 router.php` from the repo root; `router.php` strips the `/temandiabet` prefix internally so `http://localhost:8000/temandiabet/backend/api/...` resolves identically without moving folders.
  - **Production uses no prefix:** the app is served at the domain root, so `VITE_BASE_PATH=/` and `VITE_API_BASE_URL=https://<domain>/backend`, and the backend's `BASE_PATH` becomes `''`.
- **Single source for the prefix — no hardcoded copies:**
  - `frontend/vite.config.ts` reads `VITE_BASE_PATH` via `loadEnv` and uses it as Vite `base` (default `/temandiabet/`).
  - `App.tsx` sets `BrowserRouter basename={import.meta.env.BASE_URL.replace(/\/$/, '')}` → `/temandiabet` in dev, `''` at the domain root.
  - `backend/config/config.php` defines `BASE_PATH` (`/temandiabet` locally, `''` in production), consumed by session and `remember_token` cookie params.
- **Cookie Scope & CORS:**
  - PHP `session_set_cookie_params()` and `remember_token` cookies use `path = BASE_PATH !== '' ? BASE_PATH : '/'`, `httponly = true`, and `samesite = 'Lax'`. Mismatched paths silently fail to clear cookies on logout.
  - `backend/config/cors.php` uses an explicit allowlist of local origins (`http://localhost:5173`, `http://localhost`, `http://127.0.0.1:5173`) plus the production origin, with `Access-Control-Allow-Credentials: true`. Never `*`, since credentials are sent.
- **Portable Relative Upload Paths:** MySQL stores relative paths (e.g., `uploads/avatars/<hash>.png`), and the frontend resolves media URLs against `${VITE_API_BASE_URL}/<relative_path>` so images load identically under XAMPP, `php -S`, and production.
- **MySQLi Prepared Statements & IDOR Prevention:** All SQL queries use `mysqli` prepared statements; private mutations enforce `WHERE user_id = ?` from `$_SESSION['user_id']`, and Inner Circle monitoring enforces an active `'accepted'` link.
- **Client-Side Food Presets:** Common Indonesian food nutrition presets reside in a static TypeScript module (`foodPresets.ts`) to avoid adding an Admin table.

## Clinical & Safety Rules

Medical logic follows **PERKENI** (PGDM monitoring guideline) and **ADA Standards of Care in Diabetes—2026 (Section 6)**, for non-pregnant adults with diabetes. These citations live as a comment at the top of `backend/helpers/glucose.php` so they are available for the project defense.

**Classification thresholds** — one helper, `classifyGlucose(int $mgdl, string $timing): string`, called by both `create.php` and `update.php`. No threshold may be duplicated in any endpoint or in the frontend:

| `measurement_timing` | `normal` | `elevated` | `high` |
| :--- | :--- | :--- | :--- |
| `fasting` | 70 – 130 | 131 – 179 | >= 180 |
| `post_meal` | 70 – 179 | 180 – 249 | >= 250 |
| `random` | 70 – 179 | 180 – 199 | >= 200 |

- `< 70 mg/dL` → `hypoglycemia` for **all** timings (Level 1 alert; consume fast-acting carbohydrate, recheck in 15 minutes).
- `< 54 mg/dL` (Level 2) and `>= 250 mg/dL` (ketone/DKA risk) are **display-only** refinements derived in the UI from the stored value — the enum stays at four values, so the schema does not change.
- Classification is never mixed with the personal target band: `target_glucose_min` / `target_glucose_max` (defaults `80` / `130`, matching the ADA fasting target) drive the chart band only and stay editable per patient in `ProfilePage`, because targets are individualized by the treating physician.

**Safety constraints (never cut):**

- **No diagnostic verdicts.** The app shows monitoring status only (`Normal`, `Elevated`, `High`, `Hypoglycemia`). It never states that a user has diabetes or interprets a reading as a diagnosis — finger-stick SMBG is not diagnostic.
- **No automated dose advice.** `medication_schedules` is strictly a reminder checklist for the regimen prescribed by the patient's physician. No page suggests changing a dose or insulin units.
- **`measurement_timing` is required** on every glucose record so the printable report remains interpretable.
- **Consent & revocation (UU PDP No. 27/2022 Art. 4(2), health data as Specific Personal Data):** the patient must explicitly generate an `invite_code` before any health data is shared, the caregiver explicitly claims it, and either side can revoke at any time. Monitoring payloads exclude `password_hash` and `remember_token`.
- **Disclaimer** — one exported string (`DISCLAIMER` in `frontend/src/lib/clinical.ts`), rendered in the layout footer, on every alert banner, and in printed reports:
  > "Disclaimer: Teman Diabet is a personal self-monitoring tool for informational purposes only. It does not provide medical diagnoses, prescribe treatment, or replace professional medical advice. In a medical emergency (severe low or high blood sugar with symptoms), seek immediate medical care."

**References:** PERKENI — _Pedoman Pengelolaan dan Pencegahan Diabetes Melitus Tipe 2 di Indonesia_ and _Pedoman Pemantauan Glukosa Darah Mandiri (PGDM)_; ADA — _Standards of Care in Diabetes—2026, Section 6: Glycemic Goals and Hypoglycemia_; UU RI No. 27 Tahun 2022 (UU PDP) Pasal 4 Ayat (2).

## Git Workflow & Versioning

- `main` is always runnable: `php -l` on changed PHP, `npx tsc --noEmit`, and `npm run build` must be green before any merge.
- Trunk-based with short-lived branches, one per task: `feature/t2-scaffold`, `feature/t7-glucose-create`, `fix/<short-description>`. Merge into `main` with a plain `git merge`, then delete the branch. Merge within 1–3 days; long-lived branches are the cost, not the merge.
- Tasks 1–6 land sequentially on `main`. That is what locks the schema, `router.php`, CORS/cookie rules, and the `AuthContext` contract before Streams A–C fork.
- Commit per task slice, not per phase: `<type>: <what>` with `feat|fix|refactor|test|docs|chore`, e.g. `feat: add glucose create endpoint with server-side classification`.
- `.env` is never committed; `.env.example` is. `.gitkeep` is committed in `backend/uploads/**`; uploaded test images are ignored.
- Tags mark checkpoints only: `v0.1.0` (Tasks 1–2), `v0.2.0` (3–6), `v0.3.0` (7–10), `v1.0.0` (final). No CHANGELOG and no git hooks — the branch history and four tags are the release record.

## Directory Structure

```
temandiabet/
├── .gitignore
├── router.php                        # Maps /temandiabet/... for `php -S` to match XAMPP
├── tasks/
│   ├── plan.md
│   └── todo.md
├── database/
│   └── database.sql
├── backend/
│   ├── config/
│   │   ├── config.php                # BASE_PATH for cookie scope
│   │   ├── cors.php
│   │   └── db.php
│   ├── helpers/
│   │   ├── auth.php
│   │   ├── glucose.php               # classifyGlucose() + citations
│   │   ├── response.php
│   │   └── upload.php
│   ├── api/
│   │   ├── auth/
│   │   │   ├── login.php
│   │   │   ├── logout.php
│   │   │   ├── me.php
│   │   │   └── register.php
│   │   ├── profile/
│   │   │   └── update.php
│   │   ├── blood-glucose/
│   │   │   ├── create.php
│   │   │   ├── delete.php
│   │   │   ├── index.php
│   │   │   └── update.php
│   │   ├── food/
│   │   │   ├── create.php
│   │   │   ├── delete.php
│   │   │   └── index.php
│   │   ├── medication/
│   │   │   ├── create.php
│   │   │   ├── delete.php
│   │   │   ├── index.php
│   │   │   └── toggle.php
│   │   └── inner-circle/
│   │       ├── generate-code.php
│   │       ├── index.php
│   │       ├── join.php
│   │       ├── monitor.php
│   │       └── remove.php
│   └── uploads/
│       ├── .gitkeep
│       ├── avatars/
│       │   └── .gitkeep
│       └── foods/
│           └── .gitkeep
└── frontend/
    ├── .env.example
    ├── index.html
    ├── package.json
    ├── tsconfig.json
    ├── vite.config.ts
    └── src/
        ├── App.tsx
        ├── index.css
        ├── main.tsx
        ├── types/
        │   └── index.ts
        ├── lib/
        │   ├── api.ts
        │   └── clinical.ts           # DISCLAIMER, status labels, 54/250 display thresholds
        ├── data/
        │   └── foodPresets.ts
        ├── context/
        │   └── AuthContext.tsx
        ├── components/
        │   ├── AlertBanner.tsx
        │   ├── GlucoseChart.tsx
        │   ├── GlucoseForm.tsx
        │   ├── Layout.tsx
        │   └── ProtectedRoute.tsx
        └── pages/
            ├── BloodGlucosePage.tsx
            ├── DashboardPage.tsx
            ├── FoodLogPage.tsx
            ├── GlucoseReportPage.tsx
            ├── InnerCirclePage.tsx
            ├── LoginPage.tsx
            ├── MedicationPage.tsx
            ├── MonitorPatientPage.tsx
            ├── ProfilePage.tsx
            └── RegisterPage.tsx
```

## Team Coordination & Parallelization

- **Sequential First (Tasks 0–5):** One member completes repository setup + Foundation + Auth/Session so the shared database schema, `router.php`, CORS/cookie rules, and `AuthContext` contract are locked.
- **Safe to Parallelize After Checkpoint 2:**
  - **Stream A:** Blood Glucose CRUD, Chart & Print Report (Tasks 7–8)
  - **Stream B:** Food Log + Upload & Medication Checklist (Tasks 9–10)
  - **Stream C:** Inner Circle Invite/Join Flow (Task 11)
  - Shared route constants and `App.tsx` route entries land on `main` *before* the streams fork, so no stream edits `App.tsx` alone.
- **Integration (Tasks 12–13):** Read-only Inner Circle monitoring and unified Dashboard assembly once Streams A–C endpoints are merged.

## Task List

### Phase 1: Foundation

- [ ] Task 0: Initialize git repository, `.gitignore`, and baseline commit
- [ ] Task 1: Initialize MySQL schema, PHP Native core, and `/temandiabet` router shim
- [ ] Task 2: Scaffold React TS + Tailwind CSS frontend with a configurable base path

### Checkpoint: Foundation

- [ ] MySQL imports cleanly; both XAMPP and `php -S localhost:8000 router.php` respond under `/temandiabet/backend/`; React builds cleanly
- [ ] Merged via `feature/tN-...`; tag `v0.1.0`

### Phase 2: Authentication & Profile

- [ ] Task 3: User registration flow
- [ ] Task 4: User login with `BASE_PATH`-scoped Session and Remember-Me Cookie persistence
- [ ] Task 5: Logout endpoint and React protected route guard
- [ ] Task 6: User profile update and secure avatar image upload

### Checkpoint: Authentication & Profile

- [ ] Full auth lifecycle and avatar upload verified under both XAMPP and `php -S`
- [ ] Merged via `feature/tN-...`; tag `v0.2.0`

### Phase 3: Core Diabetes Tracking (Parallelizable Streams)

- [ ] Task 7: Blood glucose logging with backend auto-classification and history list
- [ ] Task 8: Blood glucose edit/delete, trend chart visualization, and printable report
- [ ] Task 9: Food log CRUD with preset selector and food photo upload
- [ ] Task 10: Medication schedule CRUD and daily compliance checklist

### Checkpoint: Core Diabetes Tracking

- [ ] All self-management CRUD slices, uploads, charts, and print report verified end-to-end
- [ ] Merged via `feature/tN-...`; tag `v0.3.0`

### Phase 4: Inner Circle & Dashboard Polish

- [ ] Task 11: Inner Circle invitation code generation, claim, and removal flow
- [ ] Task 12: Inner Circle read-only patient monitoring dashboard
- [ ] Task 13: Unified Patient Dashboard overview, extreme-glucose alert banner, and responsive layout

### Checkpoint: Complete

- [ ] All acceptance criteria met across the team
- [ ] `npx tsc --noEmit` and `npm run build` pass cleanly
- [ ] Merged via `feature/tN-...`; tag `v1.0.0`
- [ ] Ready for final review

## Risks and Mitigations

| Risk                                                                               | Impact | Mitigation                                                                                                                                                           |
| ---------------------------------------------------------------------------------- | ------ | -------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Path/prefix mismatch between dev `/temandiabet` and prefix-less production         | High   | One env value drives Vite `base` and the router `basename`; `BASE_PATH` drives cookie scope. Flip both together per `.env.example`; `.env` is gitignored with `.env.example` committed. |
| Cross-origin cookie rejection (`localhost:5173` vs `localhost` / `localhost:8000`) | High   | Never mix `127.0.0.1` and `localhost` in browser/API URLs; set explicit `Access-Control-Allow-Origin` and `Access-Control-Allow-Credentials: true`.                  |
| Git merge conflicts on `App.tsx` or `uploads/` during parallel team work           | Med    | Trunk-based short-lived branches; shared route constants merged to `main` before Streams A–C fork; commit `.gitkeep` in `backend/uploads/` and ignore uploaded test images. |
| Unauthorized data access (IDOR) or unsafe uploads                                  | High   | Enforce `$_SESSION['user_id']` checks on all queries, verify `inner_circles` `'accepted'` status on monitor reads, and validate `finfo_file()` MIME types + 2 MB cap. |
| Clinical misclassification or missing disclaimer                                   | High   | Thresholds exist only in `classifyGlucose()` with PERKENI/ADA citations; the disclaimer is one exported string; verification covers every timing band. |
