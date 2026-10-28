---
paths:
  - routes/console.php
  - routes/web.php
---

# Routes

## Stale payments must be expired and reconciled on a schedule
A Tripay callback can be lost, so payment state must not depend on the callback arriving. `tripay:expire-stale-payments` expires pending payments past `expires_at`, and `tripay:reconcile-payments` asks the gateway for the real status via `TripayClient::transactionStatus()`. Reconciliation also covers payments the local sweep expired within the last 7 days, because a late payment whose callback was lost would otherwise never be recovered.

Keep both scheduled with `withoutOverlapping()`, and keep `transactionStatus()` and `referenceOf()` in use — they are the only recovery path for an order whose callback never arrived.

Anything depending on the scheduler must be tested by calling the command directly, not by waiting on the clock.

## Keep throttle on the CSRF-exempt gateway callback
The Tripay callback and the customer pay endpoint carry `throttle` middleware, defined as named limiters in `AppServiceProvider::boot()`. The callback is CSRF-exempt so the signature is its only authentication — never remove its limiter.

Keep the callback limiter generous: the gateway legitimately retries notifications, so a tight limit would drop real payment confirmations. Keep the pay endpoint limiter tight.

`tripay.is_production` does not exist as a separate config key — `base_url` derives the host directly from `TRIPAY_IS_PRODUCTION`, so there is one source of truth for sandbox vs production.
