---
paths:
  - app/Services/OrderWorkflow.php
  - app/Services/PaymentService.php
---

# Services

## OrderStatus enum is the only order state machine
Never write `status` on an Order directly and never add a transition outside the enum. Every change goes through `OrderWorkflow::transitionTo()`, which rejects anything `OrderStatus::canTransitionTo()` disallows and writes an `order_status_histories` row so the trail is never lost.

`GATEWAY_ONLY` statuses (`Paid`, `Expired`) are reachable only when the caller passes `confirmedByGateway: true` — the Tripay callback path. `Delivered` requires the order to already have a result file. Both are domain invariants now, not controller conventions, so do not re-implement them as controller-only checks.

## Tripay webhook is the only authority for payment status
Never move an order to a paid or expired state from a browser request or a redirect return. Only `TripayNotificationController` may, and only after the callback signature verifies.

Verify the signature against the untouched raw body (`$request->getContent()`), never a re-encoded array — re-encoding can reorder keys or change escaping and then never matches. Compare with `hash_equals`.

`notifikasi/tripay` is CSRF-exempt in `bootstrap/app.php`, so the signature is its only authentication: keep the `throttle` middleware on it, and never drop the `merchant_code` check.

Payment initiation is guarded by a non-blocking `Cache::lock` with a re-check of the open payment inside the lock. Keep the outer `openPaymentFor()` check first, and keep the gateway call before the payment row is written so a rejected transaction leaves nothing behind.

## Gateway responses do not share one shape
Reconciliation uses `/transaction/detail`, not `/transaction/check-status`: only detail returns the transaction data (`status`, amounts, `paid_at`) needed to rebuild a callback payload, while check-status reports only a message. The detail response reports the invoice as `amount`, and can report `total_amount` as a list; a notification reports it as a single value. `applyCallback()` compares that figure against the invoice, so anything that builds a payload from the API has to normalise it first. Per-transaction failures inside a scheduled sweep must be caught and logged, never allowed to abort the rest of the batch.
