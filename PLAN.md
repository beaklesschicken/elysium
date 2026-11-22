# Elysium Hospitality — Build Plan

Two products behind one login at elysiumhospitality.net, sharing the same organizations, units and employees:

1. **Credentials** (build first) — multi-tenant tracking of employee and organization credentials: liquor/beverage permits, trainings, certificates, and the organization's own permits.
2. **Tips** (build second) — tip entry per pay period, tip-out calculation, and a payroll output page. Replaces spreadsheets. Tip-out options to be defined later.

The portal stays behind `APP_PORTAL_ENABLED` (off in production) until the Credentials MVP is done.

---

## Where the code stands today

What exists and is worth keeping:

- `Organization` (tenant) → `Team` (a unit/location) → managers (`User`, via `team_user`) and employees (`Employee`, via `employee_team`).
- `User` ↔ `Organization` many-to-many with a `role` on the pivot (default `admin`).
- `Credential` is a catalog of credential *types* (name, type, US state, info link, public flag, submitted_by), assigned polymorphically to users/employees/organizations through `assignables`.
- Login listener puts `working_org`, `org_teams`, `team_ids` in the session.
- Livewire + Flux UI starter kit, auth, settings, team create/add screens, deploy scripts, portal kill switch with tests.

Problems to fix before building on it:

| Issue | Where |
| --- | --- |
| `Employee::organization()` / `teams()` don't `return`, and `belongsToOne` doesn't exist | `app/Models/Employee.php` |
| `Credential::assigned()` morphs to `Credential` itself instead of to holders | `app/Models/Credential.php` |
| `Team::managers()` passes `user_id` as the team's pivot key (should be `team_id`) | `app/Models/Team.php` |
| `TeamController` queries `organization_id LIKE <array>` | `app/Http/Controllers/TeamController.php` |
| `CreateTeam` lists managers by `users.organization_id`, but membership is the `organization_user` pivot; two sources of truth | `app/Livewire/Teams/CreateTeam.php`, users table |
| `AddToTeam` attaches a *user* id as an *employee* | `app/Livewire/Teams/AddToTeam.php` |
| Whole `Organization` model and team list cached in session (goes stale; nothing scopes queries to the tenant) | `SetDefaultSessionsWhenLoggedIn` |
| Spatie permission config, migration and `TeamsPermission` middleware exist, but the package isn't installed | `config/permission.php`, migrations |
| No foreign keys or indexes on any pivot/tenant columns | migrations |
| Dashboard prints the session and user attributes | `resources/views/dashboard.blade.php` |
| A credential has no holder-specific data: no number, issue date, expiry or document | schema |

---

## Phase 0 — Foundation (shared by both products)

**Tenancy**
- Single database, every tenant-owned table has `organization_id` with a foreign key.
- `BelongsToOrganization` trait: global scope to the current organization + auto-fill on create.
- Store only `current_organization_id` in the session (or on `users`), resolved per request by middleware. Organization switcher in the sidebar for users in more than one org.
- Drop `users.organization_id`; `organization_user` is the one source of membership.

**Units**
- Keep the `teams` table/model but call it **Unit** in the UI (or rename to `units` now, while there's no real data — decide below).
- Unit fields: name, address, phone, liquor license holder, notes.

**People and roles**
- Users = people who log in (owner, admin, unit manager). Employees = people tracked, who may never log in. Optional `employees.user_id` later for self-service.
- Roles: `owner`, `admin` on `organization_user`; `manager` on `team_user` (scoped to that unit). Enforced with Laravel policies. Remove the unused Spatie leftovers unless we decide we need fine-grained permissions.
- Employees: name, email, phone, hire date, birth date (needed for age-restricted service), status (active/terminated), position(s), notes. Belongs to one org, one or more units.

**Onboarding**
- Register → create organization → add first unit → invite managers by email (signed invitation links) → add employees (form + CSV import).

**Cleanup**
- Fix the bugs listed above, add FKs/indexes in new migrations, replace the debug dashboard, factories and seeders that reflect the real model, Pest coverage for tenant isolation (org A can never see org B's data).

## Phase 1 — Credentials MVP

**Data model**
- `credential_types` (the existing `credentials` catalog, renamed): name, category (liquor/beverage server permit, food handler, training, certificate, business license/permit), issuing authority, US state, default validity in months, info link, applies to (employee / unit / organization), `organization_id` null for public types or set for an org's private types.
- `credentials` (a held credential): type, holder (polymorphic: employee, unit, organization), number, issued on, expires on, status (computed: valid / expiring / expired / missing), notes, verified by/at.
- `credential_documents`: uploaded scans/photos on the private disk, served only through an authorized controller.
- Replaces the `assignables` table.

**Screens**
- Org dashboard: counts of expired / expiring in 30 / 90 days, per unit.
- Unit page: employees with their credentials, red/amber/green status.
- Employee page: credentials, upload document, renew (new record, old one kept as history).
- Organization & unit permits page (liquor license, health permit, business license, etc.).
- Credential type catalog: browse public types by state; add private types.

**Notifications**
- Daily scheduled command finds credentials crossing 90 / 30 / 7 / 0 days and emails the unit manager and org admins (Postmark is already configured). Weekly digest option.

## Phase 2 — Credentials, round two

- **Requirements**: "every bartender at Unit X needs a state server permit"; shows gaps (missing credentials), not only expirations.
- Employee self-service: magic link for an employee to upload their own certificate; manager approves.
- Reports & exports (CSV/PDF) for inspections.
- Audit log of who changed what.
- Plans/billing (`organizations.plan` already exists) — later.

## Phase 3 — Tips (tip split)

Reuses organizations, units and employees from Phase 0.

**Concepts**
- **Positions** per unit (server, bartender, busser, host, barback, kitchen) — also useful for credential requirements.
- **Pay period**: per-unit setting, weekly or bi-weekly, with a start day. Status: open → calculated → finalized (locked).
- **Tip entries**: per employee per shift or day — hours, sales, credit-card tips, cash tips (what gets entered is configurable).
- **Tip-out rules** (to be designed with you): e.g. "servers tip out 3% of sales to bussers", "bartenders get 10% of server tips", pooled tips split by hours or points. Rules are versioned and snapshotted when a period is finalized, so past payroll never changes.
- **Calculation engine**: a plain PHP service class (no UI code) with thorough unit tests; all money in integer cents with explicit rounding and leftover-penny handling.

**Screens**
- Period entry grid (spreadsheet-like Livewire table, keyboard-friendly).
- Tip-out options/rules editor per unit.
- **Payroll page**: per-employee totals for the period (tips earned, tipped out, received, net, hours) + CSV export for the payroll provider.

## Order of work

1. ~~Phase 0 cleanup and tenancy, with tests.~~ Done 2026-11-22.
2. ~~Credentials data model + employee/unit/org screens.~~ Done 2026-11-22.
3. ~~Expiration notifications + dashboard.~~ Done 2026-11-22. Reminder emails are an organization setting, **off by default**.
4. Turn the portal on in production (Credentials MVP). ← next
5. Phase 2 credential features as needed.
6. Tips: positions, periods, entry grid → rules/engine (after we spec the options) → payroll page.

## Open questions

1. ~~Rename `teams` → `units`?~~ Done.
2. ~~Spatie or simple roles + policies?~~ Simple roles (`owner`, `admin`, `manager`) + policies; Spatie leftovers removed.
3. Which states do customers operate in? The public catalog starts with Indiana entries plus generic ones; validity periods are left blank until confirmed.
4. ~~Who receives expiration alerts?~~ Owners/admins get everything; unit managers get their units and those units' employees. Opt-in per organization.
5. Can a manager belong to multiple units / an employee work at multiple units? (Schema allows both.)
6. Tips: what's entered per shift, which tip-out methods are needed, and what format does payroll need? (Later.)
