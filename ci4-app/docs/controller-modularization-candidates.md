# Controller Modularization Candidates

The controllers were built straightforwardly first, which was the right move while the workflow was still changing. Now that PO/RR/CV and DR/PR behavior is clearer, the next cleanup should move repeated query and posting logic into services.

## Already Started

### Transaction detail loading

Current reusable pieces:
- `App\Controllers\TransactionDetails`
- `App\Services\TransactionDetailService`

What it centralizes:
- DR details for ledgers, delivery lists, void reports, and future inventory drilldowns.
- PR details for ledger modals and collection pages.
- RR/pickup details for payable ledger, pickup pages, and delivery-link context.
- CV/payable details for payable pages and supplier ledger drilldowns.

Next use sites:
- `deliveries/index.php` and `deliveries/list.php`
- `purchase_orders/index.php` and `purchase_orders/list.php`
- `payables/index.php` and `payables/list.php`
- `reports/voided/index.php`
- `payable_ledger/index.php`

## Highest Priority

### Delivery query service

Current controller: `Deliveries`

Move out:
- `fetchDeliveries()`
- delivery item, payment allocation, pickup allocation, and history queries
- unpaid/paid balance calculations reused by edit, void, quick pay, and reports

Suggested service:
- `DeliveryReadService`

Why:
- `Deliveries` is doing list filtering, modal payload assembly, validation, update rules, void rules, RR allocation rules, and print preparation.
- The same DR detail data is needed in ledger, reports, and future inventory.

### Payment query service

Current controller: `Payments`

Move out:
- `fetchPayments()`
- `fetchUnpaidDeliveries()`
- allocation and other-account summary queries

Suggested service:
- `PaymentReadService`

Why:
- Payment pages, ledger PR modals, BOA, and SOA all need payment allocation context.

### Purchase/RR query service

Current controller: `PurchaseOrders`

Move out:
- `fetchPurchaseOrders()`
- RR item lookup with source supplier PO balances
- CV allocation lookup
- delivery allocation lookup once inventory/DR linking grows

Suggested service:
- `PurchaseOrderReadService`

Why:
- RR details will be used by payable ledger, delivery connection search, inventory lots, and supplier statements.

### Supplier order query service

Current controller: `SupplierOrders`

Move out:
- supplier PO list filtering
- ordered/picked/balance calculations
- consumed RR drilldowns

Suggested service:
- `SupplierOrderReadService`

Why:
- Supplier PO balance is quantity-only and will likely feed inventory planning later.

## Posting Services To Keep Expanding

### Delivery posting service

Current state:
- Delivery create/update/void logic still lives mostly in `Deliveries`.

Suggested service:
- `DeliveryPostingService`

Move in:
- DR creation
- DR edit rules
- DR void rules
- ledger posting
- optional RR allocation replacement

Why:
- Edit and void must stay consistent once inventory movements are added.

### Purchase/RR posting service

Current state:
- Payable posting already has `PayablePostingService`.
- RR creation/update/void still lives mostly in `PurchaseOrders`.

Suggested service:
- `PurchaseOrderPostingService`

Move in:
- RR creation
- RR edit rules
- RR void rules
- payable ledger posting
- supplier PO balance consumption/restoration
- future inventory IN movement

Why:
- RR is now both an accounting document and the future inventory source.

### Supplier order posting service

Current state:
- Supplier PO create/update/void lives in `SupplierOrders`.

Suggested service:
- `SupplierOrderPostingService`

Move in:
- quantity-only PO creation
- update rules
- void rules
- ledger quantity row posting

Why:
- Supplier PO is not money-bearing, so keeping its posting rules separate from RR/CV prevents accidental amount logic from leaking in.

## Shared Helpers Worth Creating

### Date range resolver

Repeated pattern:
- `resolveDateRange()` appears in several controllers with slight differences.

Suggested helper/service:
- `DateFilterService`

Keep configurable:
- default current day
- default current month
- swap invalid start/end order
- optional empty date support

### PDF renderer

Repeated pattern:
- Dompdf setup is repeated across ledger, payments, payables, purchase orders, and reports.

Suggested helper:
- `PdfRenderService`

Input:
- view name
- data
- filename
- paper size/orientation

### Detail response helper

Current pattern:
- `TransactionDetails` has a local JSON not-found helper.

Suggested later:
- Keep it local unless more JSON controllers appear.
- If more AJAX controllers are created, extract a `JsonResponseService` or controller trait.

### Number and amount formatting

Current pattern:
- Views repeatedly call `number_format`.
- Alpine repeatedly defines `formatAmount`.

Suggested helper:
- PHP `amount()` view helper for server-rendered values.
- Shared front-end utility only if the JS grows beyond Alpine snippets.

## Inventory Placement Note

Inventory should not be bolted directly into DR or RR controllers. The clean path is:

- RR/pickup posts an inventory IN movement.
- DR posts an inventory OUT movement.
- Shortage/loss posts an adjustment OUT movement.

Suggested future services:
- `InventoryMovementService`
- `InventoryLotReadService`
- `InventoryAllocationService`

These should be called from posting services, not directly from controllers.
