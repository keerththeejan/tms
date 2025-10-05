# TMS Test Execution Run Log

Use this to record each execution and final result. Duplicate the table per cycle if needed.

## Metadata
- **Build/Commit**: 
- **Environment**: WAMP (PHP 8, MySQL 8)
- **Tester**: 
- **Date Range**: 

## Legend
- Result: PASS / FAIL / BLOCKED / NA
- Severity: S1-Blocker, S2-Critical, S3-Major, S4-Minor, S5-Cosmetic

## Run Log Table

| ExecID | TestID | Module | Action | Preconditions | Steps (short) | Expected Result | Actual Result | Result | Severity | Defect ID |
|-------:|--------|--------|--------|---------------|---------------|-----------------|---------------|--------|----------|-----------|
| 1 | TC-001 | Auth | Valid login | Admin exists | Login with admin/admin123 | Redirect to dashboard |  |  |  |  |
| 2 | TC-006 | Dashboard | Filters | Branches exist | Set df/dt/fb/tb | Counts update |  |  |  |  |
| 3 | TC-026 | Parcels | Create (non-KIL) | Logged as non-KIL | Save parcel without price | Parcel created; price null |  |  |  |  |
| 4 | TC-027 | Parcels | Create (KIL) | Logged as KIL | Add items; leave price | Price auto computed |  |  |  |  |
| 5 | TC-034 | Delivery Notes | Generate | Pending parcels exist | POST customer+date | DN created |  |  |  |  |
| 6 | TC-037 | Payments | Create | DN exists | Add payment | Payment saved |  |  |  |  |
| 7 | TC-040 | Expenses | Create | Authorized | Save expense | Created & listed |  |  |  |  |
| 8 | TC-045 | Salaries | Pay | Rows exist | Mark paid | Totals/Counts update |  |  |  |  |

## Notes
- Attach screenshots for FAIL.
- Cross-reference defects in your tracker (e.g., JIRA, GitHub Issues).
