# 📝 Test Report – Example (TMS)

- **Project Name**: Transport Management System (TMS)
- **Modules**: Dashboard, Branches, Users, Change Password, Customers, Vehicles (API), Suppliers, Parcels, Parcel Print, Delivery Notes, Payments, Expenses, Employees, Salaries, Search, Reports
- **Tested By**: <QA Team / Name>
- **Date**: 03-10-2025
- **Test Type**: Functional Testing (PASS/FAIL focus)

---

## 1. Test Summary
- **Total Test Cases**: 50 (see `tests/test_cases.csv`)
- **Passed**: <enter>
- **Failed**: <enter>
- **In Progress/Blocked**: <enter>

---

## 2. Test Environment
- **OS**: Windows 10 / 11 (64-bit)
- **Browser**: Chrome v140.0 (or latest)
- **Database**: MySQL 8.0
- **Server**: Apache 2.4, PHP 8.2
- **App Entry**: `public/index.php`

---

## 3. Test Cases (Sample)

| Test Case ID | Test Scenario | Steps Performed | Expected Result | Actual Result | Status |
|---|---|---|---|---|---|
| TC-001 | Valid Login | Enter admin/admin123, click login | Dashboard should open | <enter> | <PASS/FAIL> |
| TC-006 | Dashboard loads + filters | Open dashboard; apply df/dt/fb/tb | Metrics update w/o error | <enter> | <PASS/FAIL> |
| TC-008 | Create Branch (Admin) | New -> name+code -> Save | Branch created and listed | <enter> | <PASS/FAIL> |
| TC-012 | Create User (Admin) | Enter username/fullname/role/branch | User created; password hashed | <enter> | <PASS/FAIL> |
| TC-016 | Create Customer | Name+Phone -> Save | Customer created | <enter> | <PASS/FAIL> |
| TC-017 | Duplicate Customer Phone | Save with existing phone | Error shown; not saved | <enter> | <PASS/FAIL> |
| TC-026 | Create Parcel (non-KIL) | Fill mandatory fields (no price) | Parcel created; price NULL | <enter> | <PASS/FAIL> |
| TC-027 | Create Parcel (KIL) | Items+discount; price auto | Parcel created; price computed | <enter> | <PASS/FAIL> |
| TC-034 | Generate Delivery Note | Pick customer+date -> POST | DN created with eligible parcels | <enter> | <PASS/FAIL> |
| TC-037 | Add Payment | Add payment to DN | Payment saved; totals update | <enter> | <PASS/FAIL> |
| TC-041 | Expenses Filters | Filter by date/branch/type | Lists + summary reflect filters | <enter> | <PASS/FAIL> |
| TC-045 | Salaries Mark Paid | Set payment_date/status | Counts/totals update | <enter> | <PASS/FAIL> |
| TC-046 | Search by Phone | Enter phone | Customer + parcels/notes/due shown | <enter> | <PASS/FAIL> |
| TC-049 | Reports – Expense Summary | Apply date range | Summary loads | <enter> | <PASS/FAIL> |

> Full list available in `tests/test_cases.csv`.

---

## 4. Defect Summary (Sample)
- Defect ID: <BUG-001> – Duplicate username suggestion not shown on conflict (from `?page=users`).
- Defect ID: <BUG-002> – `?page=parcel_print` alignment issue on A5 print.

---

## 5. Conclusion
- ✔ Core modules verified (Login, Dashboard, Customers, Parcels, Delivery Notes, Payments, Reports).
- ❗ Pending fixes: <enter short list if any>
- ✅ Recommendation: <Ready / Ready with known issues / Not ready>

---

## 6. Module-wise PASS/FAIL (One-word status)

| Module | Status | Notes |
|---|---|---|
| Dashboard | <PASS/FAIL/MIXED> |  |
| Branches | <PASS/FAIL/MIXED> |  |
| Users | <PASS/FAIL/MIXED> |  |
| Change Password | <PASS/FAIL/MIXED> |  |
| Customers | <PASS/FAIL/MIXED> |  |
| Vehicles (API) | <PASS/FAIL/MIXED> |  |
| Suppliers | <PASS/FAIL/MIXED> |  |
| Parcels | <PASS/FAIL/MIXED> |  |
| Parcel Print | <PASS/FAIL/MIXED> |  |
| Delivery Notes | <PASS/FAIL/MIXED> |  |
| Payments | <PASS/FAIL/MIXED> |  |
| Expenses | <PASS/FAIL/MIXED> |  |
| Employees | <PASS/FAIL/MIXED> |  |
| Salaries | <PASS/FAIL/MIXED> |  |
| Search | <PASS/FAIL/MIXED> |  |
| Reports | <PASS/FAIL/MIXED> |  |

---

### How to finalize
1) Mark each module status in the table above.
2) Fill Actual Result + Status for the sampled cases (or all cases from CSV).
3) Save this file or export to Word/PDF.
