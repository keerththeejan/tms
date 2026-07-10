# TMS Test Plan

## 1. Overview
- **Application**: Transport Management System (PHP 8, MySQL 8)
- **Entry point**: `public/index.php`
- **Primary modules (routes in `public/index.php`)**:
  - `dashboard`
  - `routes`
  - `change_password`
  - `branches` (Admin)
  - `users` (Admin)
  - `customers`
  - `vehicles` (API-like, save only)
  - `suppliers`
  - `parcels` + `parcel_print`
  - `delivery_notes`
  - `payments`
  - `expenses`
  - `employees`
  - `salaries`
  - `search`
  - `reports`
  - `quick_add_customer` (POST only)

## 2. Scope
- Functional testing of listed modules and their actions (index/list, create, edit, delete, filters, reports, printing).
- Role-based access and permissions (admin/staff/accountant + specific capability checks in `Auth` functions).
- CSRF protection on POST actions.
- Data integrity per schema `database/schema.sql` and business rules in `public/index.php`.

## 3. Test Environment
- **Server**: WAMP on Windows
- **PHP**: 8+
- **DB**: MySQL 8+
- **DB name**: `tms_db` (or as per `config/config.php`)
- **Seed**:
  - Import `database/schema.sql`.
  - Create admin using `public/seed_admin.php` (admin/admin123).
- **Login**: `admin` / `admin123` (after running the seeder).

## 4. Roles & Permissions (from `app/Auth.php` usage in routes)
- **Admin only**: `branches`, `users`.
- **Parcel create**: `Auth::canCreateParcels()`.
- **Payments**: `Auth::canCollectPayments()`.
- **Expenses**: `Auth::canManageExpenses()`.
- **Delivery notes**: `Auth::hasAnyRole(['admin','parcel_user','staff'])`.
- All other routes require `Auth::check()`.

## 5. Data Setup
- Branches: ensure at least 3 branches (schema seeds MAIN, BR-A, BR-B).
- Users: create admin + at least 2 staff users in different branches.
- Customers: add 5+ with mix of phone, locations, types.
- Suppliers: add 3+ with branch links.
- Parcels: create across branches, with/without price, varied status, tracking numbers, vehicle nos.
- Delivery Notes: generate for customers with pending parcels.
- Payments: create partial and full payments.
- Expenses: add multiple types and approvals.
- Employees & Salaries: add employees, generate salary rows, process payments.

## 6. Test Areas & High-Level Checks
- **Authentication**: login/logout, invalid creds, CSRF.
- **Dashboard**: counts, due calculations, filters (`df`, `dt`, `fb`, `tb`, `cust`).
- **Branches**: CRUD, unique code, main branch toggle (only one main), effect on users `is_main_branch`.
- **Users**: CRUD, unique username handling, role validation, active flag, password hashing.
- **Customers**: CRUD, unique phone, optional geo fields, JSON summary (`action=summary`).
- **Vehicles**: `save` returns JSON, duplicate save returns existing id.
- **Suppliers**: CRUD with branch, filtering.
- **Parcels**:
  - Create/edit policy per branch (Kilinochchi price behavior).
  - Tracking number NULL vs '' handling.
  - Items, weight derivation, lorry_full session flag.
  - Delete: cascade updates to `delivery_note_parcels` and DN totals.
  - Index filters: q, status, vehicle_no, dates, customer, to_branch.
  - Print view works.
- **Delivery Notes**: generate, attach eligible parcels, prevent duplicates via unique key, totals.
- **Payments**: add payments, date range filters, due grouping.
- **Expenses**: types, approvals, filters by date/branch/approved/type.
- **Employees**: CRUD + filters; licenses and vehicle linking fields (as in view forms).
- **Salaries**: month, month_num uniqueness, status, aggregates, trends.
- **Search**: by phone/name; show parcels, notes, dues.
- **Reports**: revenue by branch, parcels by supplier, expense summary, due collections; filters.
- **Security**: all POSTs require valid CSRF; unauthorized access returns 403.

## 7. Non-Functional
- Basic performance on lists (LIMITs present in queries).
- Error handling: friendly messages, 404/405 for wrong actions.

## 8. Entry/Exit Criteria
- **Entry**: Environment ready, seed data created, admin login works.
- **Exit**: All critical tests pass; blockers resolved; test run log filled; defects logged.

## 9. Risks & Assumptions
- Browser tests on recent Chrome.
- Email/SMS not in scope.

## 10. Deliverables
- `tests/test_cases.csv`
- `tests/test_runlog.md`
- `tests/google_form_structure.txt`
- Final summary report (export from run log and/or Google Form responses).
