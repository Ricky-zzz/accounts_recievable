# Part 1 Smoke Test and Part 2 Readiness

## Purpose
This checklist verifies that Part 1 changes are stable and that Part 2 can safely build on top of them.

## Pre-Run
- Run migrations on a fresh database.
- Ensure sample data exists for clients, products, deliveries, and payments.
- Confirm application starts without errors.

## Core Part 1 Smoke Tests

### A. Client fields
- Create client without credit_limit and payment_term.
- Create client with credit_limit and payment_term.
- Edit both fields and confirm values persist.
- Validate negative values are rejected.

### B. Delivery term and due date
- Create DR with no manual term and confirm it uses client default payment_term.
- Create DR with manual override term and confirm stored value is override.
- Confirm due_date is computed and stored from DR date plus effective term.
- Confirm term and due_date appear in deliveries list and client deliveries list.
- Confirm term and due_date appear in printable deliveries report.

### C. Credit limit guard
- Create DR within credit limit and confirm save succeeds.
- Create DR that exceeds projected client balance and confirm save is blocked.
- Confirm blocked save does not create partial rows in deliveries, delivery_items, or ledger.

### D. Existing payment and ledger flow (regression)
- Post a payment allocation to one or more DRs.
- Confirm unpaid balances reduce correctly.
- Confirm ledger still renders and running balance looks correct.
- Confirm DR/PR drilldowns still open and show allocation/item details.

### E. Report and UI regression
- Open clients, deliveries, payments, ledger pages and confirm no runtime errors.
- Open delivery modals/list pages and verify no Alpine or JS errors.
- Print deliveries report and confirm PDF renders.

## Part 2 Dependency Gate (Must Pass)
All items below must pass before starting Part 2:
- Client defaults are persisted and retrievable.
- DR payment_term and due_date are always present for new records.
- Credit-limit enforcement is deterministic and blocks over-limit DRs.
- Payment allocation and ledger behavior remain unchanged.
- No migration or runtime exceptions in production-like flow.

## Suggested Test Data Matrix
- Client A: no default term, no credit limit.
- Client B: default term only.
- Client C: default term plus strict credit limit.
- DR cases: same-day due, 7-day term, 30-day term.
- Payment cases: full allocation, partial allocation, multiple DR allocation.

## Sign-off
- QA Owner:
- Date:
- Environment:
- Result: Pass or Fail
- Notes:
