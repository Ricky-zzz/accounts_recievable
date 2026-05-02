# AJAX Refactor Candidates

This app currently uses server-rendered PHP views with JSON embedded into Alpine.js. That is fine while each page sends a small or moderate amount of data. If pages start loading slowly, browser memory grows, or modals feel delayed, refactor the heaviest JSON payloads into AJAX endpoints first.

## Highest Priority

### Delivery create/edit pricing data

Current pattern:
- `Deliveries::buildFormData()` sends all products, clients, and the full client-specific price map into `deliveries/form.php`.
- `Deliveries::buildDeliveryActionData()` sends all products and all special prices into delivery list/index edit modals.

Refactor when:
- Product count is large.
- Client count is large.
- Many client-product special prices exist.

Suggested AJAX direction:
- Keep the product dropdown lightweight.
- Fetch only the needed price when client/product changes:
  - `GET /pricing/product/{productId}?client_id={clientId}`
- Return `{ unit_price, source }`, where `source` is `special` or `default`.

### Delivery details, allocations, and history modals

Current pattern:
- `deliveries/index.php` and `deliveries/list.php` preload `itemsByDelivery`, `allocationsByDelivery`, and `historiesByDelivery` for the current page.

Refactor when:
- Each delivery has many items.
- Histories become long.
- Opening the deliveries page becomes slow even before the user opens any modal.

Suggested AJAX direction:
- Load modal content only when clicked:
  - `GET /deliveries/{deliveryId}/items`
  - `GET /deliveries/{deliveryId}/allocations`
  - `GET /deliveries/{deliveryId}/history`

## Medium Priority

### Ledger detail modals

Current pattern:
- `ledger/index.php` embeds delivery items, delivery allocations, payment allocations, payment other accounts, and payments by ID.

Refactor when:
- Ledger pages cover long date ranges.
- Users often load a ledger but only inspect a few rows.

Suggested AJAX direction:
- Keep the ledger rows server-rendered and paginated.
- Fetch row details only when a user opens a detail modal.

### Payments and payables pages

Current pattern:
- `payments/index.php`, `payments/list.php`, `payables/index.php`, and `payables/list.php` preload allocation and other-account maps for modal interactions.

Refactor when:
- Payment/payable lists are large.
- Each payment has many allocations.

Suggested AJAX direction:
- Fetch allocation summaries by payment/payable ID when the modal opens.

### Purchase order pages

Current pattern:
- `purchase_orders/index.php` and `purchase_orders/list.php` preload purchase orders, items, allocations, histories, products, and suppliers.

Refactor when:
- Product/supplier lists become large.
- Purchase order histories or item lists become heavy.

Suggested AJAX direction:
- Fetch products/suppliers through searchable endpoints.
- Fetch PO items/history only when editing or opening details.

## Lower Priority

### Simple master data modals

Current pattern:
- Pages like clients, products, banks, cashiers, and suppliers encode the current paginated rows for Alpine edit modals.

Refactor when:
- The paginated page itself is still slow.
- Rows contain large extra fields.

Suggested AJAX direction:
- Keep current JSON approach unless there is a clear slowdown.
- If needed, fetch a single record for edit:
  - `GET /clients/{id}`
  - `GET /products/{id}`

### Voided reports

Current pattern:
- Voided reports preload items, allocations, histories, and rows.

Refactor when:
- Reports span many records.
- Users open only a few detail sections.

Suggested AJAX direction:
- Keep report rows server-rendered.
- Fetch voided record details on demand.

## Practical Rule

Refactor to AJAX when one of these becomes true:
- Page HTML becomes very large.
- First page load feels slow.
- Alpine initialization feels delayed.
- The browser struggles when opening modals.
- Most embedded JSON is not used by most users.

Do not refactor everything at once. Start with the page that is slow, then move only the largest unused JSON payload behind a small endpoint.
