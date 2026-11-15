# Task List: Teman Diabet MVP (`/temandiabet`)

## Status

Updated 2026-11-15. Tasks 0–2 are implemented, verified, and pushed to
`github.com/thheor/temandiabet` (`main`). Next up: Task 3.

| Area | What exists | How it was verified |
| :--- | :--- | :--- |
| Git | `main` trunk, 6 commits, uploads/`.env`/`dist` ignored | `git check-ignore`, `git ls-files backend/uploads`, clean tree |
| Database | `database/database.sql` imported into docker MariaDB 10.4.34, 5 tables | `SHOW TABLES` and a `SELECT COUNT(*) FROM users` through `db()` |
| PHP core | `config.php` (`BASE_PATH`), `db.php` (`mysqli` + `local.php` override), `cors.php`, `helpers/response.php` | `php -l`; live `OPTIONS` → `204` with echoed origin and credentials; unknown origin gets no `Allow-Origin` |
| Router | `/temandiabet` prefix under `php -S`, dist assets, SPA fallback, JSON 404 for missing API, `..` guard | live curls: `assets/*.js` → `text/javascript` 258 674 B, `assets/*.css` → `text/css`, missing asset `404`, `/backend/api/nope.php` → `404` JSON, no prefix → `404` |
| Frontend | Vite 8 + React 19 + TS 7 + Tailwind 4; `VITE_BASE_PATH` → `base`, `BASE_URL` → `basename`; typed models; credentialed client | `tsc -b` clean; builds with `VITE_BASE_PATH=/temandiabet/` and `/` both produced correct asset paths |
| Docs | `README.md` run instructions; `.env.example` with php -S, XAMPP and production setups | – |

**Deviations from the original plan**

- Docker MariaDB publishes on **3308**, not 3307 — 3307 is already bound on this machine. `mariadb:10.4` resolves to 10.4.34, matching XAMPP's line.
- Added `backend/config/local.example.php` plus a gitignored `local.php`: docker's `temandiabet` user and XAMPP's `root`/`3306` cannot share one hardcoded credential set.
- Added `README.md` (not in the plan tree).
- Toolchain versions come from the current Vite template (Vite 8, React 19, TypeScript 7, Tailwind 4 via `@tailwindcss/vite`, so no `tailwind.config.js`). `strict: true` was added to `tsconfig.app.json`.
- Tasks 0–2 were committed straight to `main`; foundation work is sequential per the plan.

**Still open in Tasks 0–2**

- XAMPP parity — the friend's machine must import the schema and serve from `htdocs/temandiabet/`.
- Visual check in a browser at `http://localhost:5173/temandiabet/`.
- `v0.1.0` tag and any `feature/` branches were not created.

## Task 0: Initialize git repository, `.gitignore`, and baseline commit

**Description:** Create the repository with `main` as the trunk, add a `.gitignore` covering dependencies, build output, local env files, and uploaded test images (while keeping `backend/uploads/**/.gitkeep`), and commit the plan, task list, and schema as the baseline.

**Acceptance criteria:**

- [x] `git init -b main` creates a repo whose trunk is `main`; short-lived branches are named `feature/tN-<slug>` and merged with a plain `git merge`.
- [x] `.gitignore` excludes `node_modules/`, `frontend/dist/`, `frontend/.env`, `*.log`, `.DS_Store`, and all files under `backend/uploads/` except the `.gitkeep` placeholders.
- [x] Baseline commit exists for `tasks/`, `database/`, and `.gitignore`.

**Verification:**

- [x] Tests pass: `git check-ignore -v frontend/.env backend/uploads/avatars/test.png` reports both ignored, while `git ls-files backend/uploads` shows only `.gitkeep` files.
- [x] Build succeeds: `git status` is clean on `main`.
- [x] Manual check: `git log --oneline` shows one `chore:` baseline commit.

**Dependencies:** None

**Files likely touched:**

- `.gitignore`

**Estimated scope:** Small (1 file)

---

## Task 1: Initialize MySQL schema, PHP Native core, and `/temandiabet` router shim

**Description:** Finalize the 5-table MySQL DDL script (`database.sql`) with English identifiers, create the shared PHP `mysqli` database connector, the `BASE_PATH` config constant, the credentialed CORS handler, JSON response helpers, and a lightweight `router.php` so `php -S localhost:8000 router.php` mirrors XAMPP's `/temandiabet` URL prefix.

**Acceptance criteria:**

- [x] `database/database.sql` creates `users`, `blood_glucose_records`, `food_logs`, `medication_schedules`, and `inner_circles` in database `temandiabet` with exact keys and cascading FKs.
- [x] `backend/config/config.php` defines `BASE_PATH` (`/temandiabet` locally, `''` in production) consumed by cookie-scope code.
- [x] `backend/config/db.php` and `cors.php` connect via `mysqli` (`utf8mb4`), handle `OPTIONS` preflight (`204`), and echo allowed local origins (plus the production origin) with `Access-Control-Allow-Credentials: true`.
- [ ] Running `php -S localhost:8000 router.php` serves `/temandiabet/backend/...` identically to Apache XAMPP at `http://localhost/temandiabet/backend/...`; `router.php` strips the `/temandiabet` prefix. *(php -S side verified; XAMPP pending)*

**Verification:**

- [x] Tests pass: Import `database/database.sql` into MySQL and run `php -l` across `router.php` and `backend/**/*.php`.
- [x] Build succeeds: `curl -i -X OPTIONS http://localhost:8000/temandiabet/backend/api/auth/me.php` (and XAMPP `http://localhost/temandiabet/...`) returns `204` with CORS headers. *(verified against a temporary probe endpoint — `me.php` arrives in Task 4; XAMPP leg pending)*
- [x] Manual check: Confirm all 5 tables exist via `SHOW TABLES`.

**Dependencies:** Task 0

**Files likely touched:**

- `database/database.sql`
- `backend/config/config.php`
- `backend/config/db.php`
- `backend/config/local.example.php`
- `backend/config/cors.php`
- `backend/helpers/response.php`
- `router.php`

**Estimated scope:** Medium (6 files)

---

## Task 2: Scaffold React TS + Tailwind CSS frontend with a configurable base path

**Description:** Initialize the Vite + React + TypeScript + Tailwind CSS app whose `base` comes from `VITE_BASE_PATH` (default `/temandiabet/`), document `.env.example` for XAMPP, `php -S`, and prefix-less production, define TypeScript models for all 5 tables, and create a credentialed `fetch` API client.

**Acceptance criteria:**

- [x] `frontend/vite.config.ts` reads `VITE_BASE_PATH` via `loadEnv` and uses it as `base` (default `/temandiabet/`); Tailwind CSS renders utility styles properly.
- [x] `frontend/.env.example` documents `VITE_BASE_PATH=/temandiabet/` + `VITE_API_BASE_URL` for XAMPP (`http://localhost/temandiabet/backend`) and `php -S` (`http://localhost:8000/temandiabet/backend`), and the production pair `VITE_BASE_PATH=/` + `VITE_API_BASE_URL=https://<domain>/backend` (no prefix).
- [x] `App.tsx` sets `basename={import.meta.env.BASE_URL.replace(/\/$/, '')}` so dev resolves `/temandiabet` and production resolves `/`.
- [x] `src/types/index.ts` and `src/lib/api.ts` define typed payloads and send `credentials: 'include'` on all API calls.

**Verification:**

- [x] Tests pass: `npx tsc --noEmit` passes with zero errors. *(run as `tsc -b`, the repo's build script)*
- [x] Build succeeds: `npm run build` produces `dist/` with `/temandiabet/` asset paths; a second build with `VITE_BASE_PATH=/` produces root-relative paths.
- [ ] Manual check: Open `http://localhost:5173/temandiabet/` in browser and verify page renders with Tailwind styles.

**Dependencies:** Task 0 (can run in parallel with Task 1)

**Files likely touched:**

- `frontend/package.json`
- `frontend/vite.config.ts`
- `frontend/.env.example`
- `frontend/src/types/index.ts`
- `frontend/src/lib/api.ts`

**Estimated scope:** Medium (5 files)

---

## Checkpoint: After Tasks 1–2 (Foundation)

- [x] Schema imported in MySQL; `php -l` and `npm run build` pass
- [ ] Both `php -S localhost:8000 router.php` and XAMPP resolve `/temandiabet/backend/` identically
- [ ] Team members verify `.env` points to their local backend URL
- [ ] Merged to `main` via `feature/tN-...` branches; tag `v0.1.0` created

---

## Task 3: User registration flow

**Description:** Implement the vertical slice for new user registration with duplicate-email checks, `PASSWORD_BCRYPT` hashing via MySQLi prepared statements, and the React `/temandiabet/register` page.

**Acceptance criteria:**

- [ ] `POST /temandiabet/backend/api/auth/register.php` validates input, rejects duplicate emails with HTTP `409`, and inserts hashed passwords into `users`.
- [ ] `RegisterPage.tsx` collects `full_name`, `email`, `password`, and `diabetes_type` (`type_1`, `type_2`, `prediabetes`, `caregiver`) with inline validation errors.
- [ ] Successful registration redirects to `/temandiabet/login` with a confirmation message.

**Verification:**

- [ ] Tests pass: `php -l backend/api/auth/register.php` and `npx tsc --noEmit` pass.
- [ ] Build succeeds: `npm run build` succeeds.
- [ ] Manual check: Register a user via UI, confirm `password_hash` is a bcrypt hash in `users`, and confirm duplicate email displays an error.

**Dependencies:** Task 1, Task 2

**Files likely touched:**

- `backend/api/auth/register.php`
- `frontend/src/pages/RegisterPage.tsx`
- `frontend/src/App.tsx`

**Estimated scope:** Medium (3 files)

---

## Task 4: User login with `BASE_PATH`-scoped Session and Remember-Me Cookie persistence

**Description:** Implement PHP authentication middleware (`requireAuth()`), login (`login.php`), and active-session check (`me.php`) using `BASE_PATH`-scoped `PHPSESSID` and `remember_token` cookies, paired with React `AuthContext` and `LoginPage`.

**Acceptance criteria:**

- [ ] `login.php` verifies passwords via `password_verify()`, initializes `$_SESSION['user_id']` with `path = BASE_PATH ?: '/'`, `httponly`, `samesite=Lax`, and sets a hashed 7-day `remember_token` cookie when "Remember me" is checked.
- [ ] `me.php` and `requireAuth()` validate `$_SESSION['user_id']` or auto-login via a valid `remember_token`, returning HTTP `401` otherwise.
- [ ] `AuthContext` restores user state on reload by calling `/api/auth/me.php` and redirects authenticated users to `/temandiabet/dashboard`.

**Verification:**

- [ ] Tests pass: `php -l` on auth PHP files and `npx tsc --noEmit` pass.
- [ ] Build succeeds: `npm run build` succeeds.
- [ ] Manual check: Log in with "Remember me", inspect DevTools Cookies (`Path = /temandiabet` locally, `/` in production, `HttpOnly`), reload page, and verify session persists.

**Dependencies:** Task 3

**Files likely touched:**

- `backend/helpers/auth.php`
- `backend/api/auth/login.php`
- `backend/api/auth/me.php`
- `frontend/src/context/AuthContext.tsx`
- `frontend/src/pages/LoginPage.tsx`

**Estimated scope:** Medium (5 files)

---

## Task 5: Logout endpoint and React protected route guard

**Description:** Implement server-side session destruction and cookie clearing on logout, plus the React `ProtectedRoute` wrapper.

**Acceptance criteria:**

- [ ] `POST /temandiabet/backend/api/auth/logout.php` clears `remember_token` in MySQL, expires `remember_token` and `PHPSESSID` cookies with the same `path` used at login, and destroys the session.
- [ ] `ProtectedRoute` redirects unauthenticated visitors to `/temandiabet/login` and shows a loading indicator during initial session check.
- [ ] Clicking Logout clears frontend auth state and blocks back/reload access to protected pages.

**Verification:**

- [ ] Tests pass: `php -l backend/api/auth/logout.php` and `npx tsc --noEmit` pass.
- [ ] Build succeeds: `npm run build` succeeds.
- [ ] Manual check: Access `/temandiabet/dashboard` logged out (redirects to login); log in, click Logout, and confirm cookies are expired.

**Dependencies:** Task 4

**Files likely touched:**

- `backend/api/auth/logout.php`
- `frontend/src/components/ProtectedRoute.tsx`
- `frontend/src/App.tsx`

**Estimated scope:** Small (3 files)

---

## Task 6: User profile update and secure avatar image upload

**Description:** Build a reusable PHP image upload helper (`finfo_file()` MIME check, `<= 2 MB`, random filename saved under `backend/uploads/`) and wire profile editing so users can update individualized clinical targets and upload an avatar photo.

**Acceptance criteria:**

- [ ] `backend/helpers/upload.php` accepts only `jpg`, `png`, and `webp` up to 2 MB, saves files under `backend/uploads/avatars/`, and returns a portable relative path (`uploads/avatars/...`).
- [ ] `POST /temandiabet/backend/api/profile/update.php` updates `full_name`, `diabetes_type`, `target_glucose_min`, `target_glucose_max`, and `avatar_path` for `$_SESSION['user_id']`; targets remain patient-editable because they are physician-prescribed, not app-computed.
- [ ] `ProfilePage.tsx` allows editing profile fields, previewing/uploading an avatar, and displaying it via `${VITE_API_BASE_URL}/${user.avatar_path}`.

**Verification:**

- [ ] Tests pass: `php -l` on `upload.php` and `update.php`, and `npx tsc --noEmit` pass.
- [ ] Build succeeds: `npm run build` succeeds.
- [ ] Manual check: Upload a valid PNG (< 2 MB) and verify it loads on both `php -S` and XAMPP; reject a `.txt` or > 2 MB file with HTTP `400`.

**Dependencies:** Task 4, Task 5

**Files likely touched:**

- `backend/helpers/upload.php`
- `backend/api/profile/update.php`
- `frontend/src/pages/ProfilePage.tsx`

**Estimated scope:** Medium (3 files)

---

## Checkpoint: After Tasks 3–6 (Authentication & Profile)

- [ ] All lint, TypeScript, and Vite build checks pass
- [ ] Register -> Login (Session + Remember Me) -> Profile/Avatar Upload -> Logout verified end-to-end
- [ ] Team review before splitting into parallel Phase 3 feature branches
- [ ] Merged to `main` via `feature/tN-...` branches; tag `v0.2.0` created

---

## Task 7: Blood glucose logging with backend auto-classification and history list

**Description:** Build the core blood glucose create and read slice. `backend/helpers/glucose.php` exposes `classifyGlucose()` — the single source of PERKENI/ADA thresholds — and the React UI provides a quick entry form with required measurement timing and a color-coded history list.

**Acceptance criteria:**

- [ ] `POST /temandiabet/backend/api/blood-glucose/create.php` validates `glucose_mg_dl > 0` and a required `measurement_timing`, assigns `status_category` server-side via `classifyGlucose()`, and inserts for `$_SESSION['user_id']`.
- [ ] Classification matches the guideline exactly: `< 70` → `hypoglycemia`; `fasting` 70–130 `normal`, 131–179 `elevated`, `>= 180` `high`; `post_meal` 70–179 `normal`, 180–249 `elevated`, `>= 250` `high`; `random` 70–179 `normal`, 180–199 `elevated`, `>= 200` `high`.
- [ ] `GET /temandiabet/backend/api/blood-glucose/index.php?range=7d|30d` returns filtered records and summary stats (`avg`, `min`, `max`).
- [ ] `BloodGlucosePage.tsx` renders `GlucoseForm`, a `7d`/`30d` range filter, and color-coded status badges labelled `Normal` / `Elevated` / `High` / `Hypoglycemia` — never a diagnosis.

**Verification:**

- [ ] Tests pass: `php -l` on `create.php`/`index.php`/`glucose.php` and `npx tsc --noEmit` pass.
- [ ] Build succeeds: `npm run build` succeeds.
- [ ] Manual check: Submit `65`, `110 (fasting)`, `165 (fasting)`, and `230 (fasting)` and confirm persisted `status_category` values are `hypoglycemia`, `normal`, `elevated`, `high`; submit `200 (post_meal)` → `elevated` and `200 (random)` → `high`.

**Dependencies:** Task 5

**Files likely touched:**

- `backend/helpers/glucose.php`
- `backend/api/blood-glucose/create.php`
- `backend/api/blood-glucose/index.php`
- `frontend/src/components/GlucoseForm.tsx`
- `frontend/src/pages/BloodGlucosePage.tsx`

**Estimated scope:** Medium (5 files)

---

## Task 8: Blood glucose edit/delete, trend chart visualization, and printable report

**Description:** Complete blood glucose CRUD with owner-scoped update/delete endpoints, add an interactive Recharts trend chart with the patient's target band, and build a print-friendly report page (`window.print()`) that carries measurement timing and the disclaimer.

**Acceptance criteria:**

- [ ] `update.php` and `delete.php` enforce `WHERE id = ? AND user_id = ?`; `update.php` recalculates `status_category` through `classifyGlucose()`.
- [ ] `GlucoseChart.tsx` plots readings over time alongside `target_glucose_min` and `target_glucose_max` reference lines (target band only — not the classification thresholds).
- [ ] `GlucoseReportPage.tsx` (route `/temandiabet/report`) renders a date-filtered summary table including `measurement_timing`, uses Tailwind `print:` classes, and prints the shared `DISCLAIMER` text.

**Verification:**

- [ ] Tests pass: `php -l` on `update.php`/`delete.php` and `npx tsc --noEmit` pass.
- [ ] Build succeeds: `npm run build` succeeds.
- [ ] Manual check: Edit a reading's value and timing, confirm the stored `status_category` changes, verify chart updates, and inspect Print Preview at `/temandiabet/report` for the timing column and disclaimer.

**Dependencies:** Task 7

**Files likely touched:**

- `backend/api/blood-glucose/update.php`
- `backend/api/blood-glucose/delete.php`
- `frontend/src/components/GlucoseChart.tsx`
- `frontend/src/pages/GlucoseReportPage.tsx`

**Estimated scope:** Medium (4 files)

---

## Task 9: Food log CRUD with preset selector and food photo upload

**Description:** Implement daily meal logging with client-side Indonesian food presets (`foodPresets.ts`), optional meal photo uploads via `upload.php`, daily calorie/carb totals, and meal deletion.

**Acceptance criteria:**

- [ ] `GET /temandiabet/backend/api/food/index.php?eaten_date=YYYY-MM-DD` returns meals for the selected date plus total `estimated_calories` and `carbohydrates_grams`.
- [ ] `create.php` handles `multipart/form-data` with optional `photo_path` upload, and `delete.php` removes both the DB record and uploaded image for `$_SESSION['user_id']`.
- [ ] `FoodLogPage.tsx` supports picking from `foodPresets.ts` (auto-filling calories/carbs), manual entry, and photo preview.

**Verification:**

- [ ] Tests pass: `php -l` on `backend/api/food/*.php` and `npx tsc --noEmit` pass.
- [ ] Build succeeds: `npm run build` succeeds.
- [ ] Manual check: Log a preset meal with an image, verify daily totals update, then delete the meal and confirm the image file is removed.

**Dependencies:** Task 5, Task 6

**Files likely touched:**

- `backend/api/food/index.php`
- `backend/api/food/create.php`
- `backend/api/food/delete.php`
- `frontend/src/data/foodPresets.ts`
- `frontend/src/pages/FoodLogPage.tsx`

**Estimated scope:** Medium (5 files)

---

## Task 10: Medication schedule CRUD and daily compliance checklist

**Description:** Implement the daily medication and insulin schedule manager as a reminder checklist only — allow users to add entries, toggle completion (`is_taken`), and remove entries. No page may suggest or recalculate a dose.

**Acceptance criteria:**

- [ ] `GET /temandiabet/backend/api/medication/index.php?scheduled_date=YYYY-MM-DD` returns scheduled items sorted by `scheduled_time` with taken/total counts.
- [ ] `create.php`, `toggle.php`, and `delete.php` enforce `user_id = $_SESSION['user_id']` and persist `is_taken` (`0`/`1`).
- [ ] `MedicationPage.tsx` provides an interactive checklist with immediate progress-bar updates and zero dosage-change suggestions.

**Verification:**

- [ ] Tests pass: `php -l` on `backend/api/medication/*.php` and `npx tsc --noEmit` pass.
- [ ] Build succeeds: `npm run build` succeeds.
- [ ] Manual check: Create two medication entries, check off one, reload page, and verify persistence (`1/2`).

**Dependencies:** Task 5

**Files likely touched:**

- `backend/api/medication/index.php`
- `backend/api/medication/create.php`
- `backend/api/medication/toggle.php`
- `backend/api/medication/delete.php`
- `frontend/src/pages/MedicationPage.tsx`

**Estimated scope:** Medium (5 files)

---

## Checkpoint: After Tasks 7–10 (Core Diabetes Tracking)

- [ ] Glucose CRUD, Recharts trend graph, and printable report verified
- [ ] Classification verified against every timing band in the plan table
- [ ] Food log with presets & photo upload verified
- [ ] Medication checklist persistence verified
- [ ] Merge parallel feature branches and verify zero conflicts before Phase 4
- [ ] Merged to `main` via `feature/tN-...` branches; tag `v0.3.0` created

---

## Task 11: Inner Circle invitation code generation, claim, and removal flow

**Description:** Build the family-linking workflow where a patient generates a unique `GLK-XXXXX` invitation code, another user claims it (also declaring the `relationship`, which the schema requires), and either side can revoke the link. Generating a code is the patient's explicit consent to share health data under UU PDP.

**Acceptance criteria:**

- [ ] `generate-code.php` creates a unique `invite_code` with `status = 'pending'` owned by `patient_id = $_SESSION['user_id']`, and the UI states what data becomes visible to the caregiver.
- [ ] `join.php` rejects self-invites, invalid codes, and already-claimed codes; requires `relationship` (`parent`, `child`, `spouse`, `sibling`, `friend`, `caregiver`); binds `caregiver_id` with `status = 'accepted'`.
- [ ] `index.php` returns both `caregivers_of_me` and `patients_i_monitor` lists, and `remove.php` allows deletion only by the linked `patient_id` or `caregiver_id`.

**Verification:**

- [ ] Tests pass: `php -l` on `backend/api/inner-circle/*.php` and `npx tsc --noEmit` pass.
- [ ] Build succeeds: `npm run build` succeeds.
- [ ] Manual check: Generate code on Account A, claim on Account B, verify both accounts list the connection and the stored `relationship`, then test removal from both sides.

**Dependencies:** Task 5

**Files likely touched:**

- `backend/api/inner-circle/index.php`
- `backend/api/inner-circle/generate-code.php`
- `backend/api/inner-circle/join.php`
- `backend/api/inner-circle/remove.php`
- `frontend/src/pages/InnerCirclePage.tsx`

**Estimated scope:** Medium (5 files)

---

## Task 12: Inner Circle read-only patient monitoring dashboard

**Description:** Implement the caregiver read-only monitoring endpoint and UI displaying a linked patient's latest glucose status, extreme-level alerts, 7-day trend chart, and today's medication adherence without edit controls.

**Acceptance criteria:**

- [ ] `GET /temandiabet/backend/api/inner-circle/monitor.php?patient_id=...` verifies an `'accepted'` row in `inner_circles` for `(patient_id, caregiver_id = $_SESSION['user_id'])` and returns HTTP `403` otherwise.
- [ ] Payload excludes sensitive columns (`password_hash`, `remember_token`) while returning profile summary, 7-day glucose logs, and today's medication status.
- [ ] `MonitorPatientPage.tsx` displays a clear **Read-Only** badge, the patient's chart, alerts, the disclaimer, and zero mutation buttons.

**Verification:**

- [ ] Tests pass: `php -l backend/api/inner-circle/monitor.php` and `npx tsc --noEmit` pass.
- [ ] Build succeeds: `npm run build` succeeds.
- [ ] Manual check: View linked Account A from Account B (succeeds, no password fields in the JSON response); attempt accessing an unlinked `patient_id` (returns HTTP `403`); after removal, access returns `403`.

**Dependencies:** Task 7, Task 8, Task 10, Task 11

**Files likely touched:**

- `backend/api/inner-circle/monitor.php`
- `frontend/src/pages/MonitorPatientPage.tsx`
- `frontend/src/App.tsx`

**Estimated scope:** Small (3 files)

---

## Task 13: Unified Patient Dashboard overview, extreme-glucose alert banner, and responsive layout

**Description:** Build the main `/temandiabet/dashboard` overview aggregating today's latest glucose reading, the alert banner, 7-day mini trend chart, daily calories, and medication progress inside a responsive Tailwind navigation shell.

**Acceptance criteria:**

- [ ] `AlertBanner.tsx` renders a warning whenever the latest `status_category` is `hypoglycemia` or `high`; `< 54 mg/dL` gets the urgent Level-2 treatment, and `>= 250 mg/dL` adds the hydrate/check-ketones/contact-a-doctor note.
- [ ] `frontend/src/lib/clinical.ts` exports `DISCLAIMER`, `HYPO_CRITICAL = 54`, `KETONE_WARNING = 250`, and the status label/color map; `Layout.tsx` footer, `AlertBanner`, and the report reuse it (no duplicated disclaimer text or thresholds).
- [ ] `DashboardPage.tsx` displays live summary cards for glucose, calories, medication adherence, and quick links to log data.
- [ ] `Layout.tsx` provides responsive desktop sidebar and mobile navigation under `/temandiabet/*` without horizontal overflow.

**Verification:**

- [ ] Tests pass: `npx tsc --noEmit` passes cleanly.
- [ ] Build succeeds: `npm run build` passes cleanly.
- [ ] Manual check: Log `220 mg/dL (fasting)` and verify the banner renders on `/temandiabet/dashboard`; log `45 mg/dL` and verify the urgent critical state; confirm the disclaimer appears in the footer, banner, and report; check layout at `375px` mobile width.

**Dependencies:** Task 8, Task 9, Task 10, Task 12

**Files likely touched:**

- `frontend/src/lib/clinical.ts`
- `frontend/src/components/AlertBanner.tsx`
- `frontend/src/components/Layout.tsx`
- `frontend/src/pages/DashboardPage.tsx`

**Estimated scope:** Medium (4 files)

---

## Checkpoint: After Tasks 11–13 (Complete)

- [ ] All acceptance criteria across Tasks 0–13 met
- [ ] `npx tsc --noEmit`, `npm run build`, and `php -l` pass cleanly
- [ ] Verified on both PHP Native (`php -S localhost:8000 router.php`) and XAMPP (`http://localhost/temandiabet/`)
- [ ] Prefix-less production build verified with `VITE_BASE_PATH=/` and `BASE_PATH=''`
- [ ] Merged to `main` via `feature/tN-...` branches; tag `v1.0.0` created
- [ ] Ready for final group review
