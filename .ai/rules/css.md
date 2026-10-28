---
paths:
  - resources/css/app.css
---

# Css

## Public and customer pages are scoped to body.playful, admin stays Spark
Public, customer, auth and error layouts carry `body.playful` and use the mint/pastel design system; the admin layout uses the Spark forest-dark theme and must stay untouched.

`app.css` is scoped under `body.playful` on purpose and re-points Spark's own CSS variables to the playful palette. Keep everything inside that scope, or the admin theme breaks.

Spark's Bootstrap and vendor assets are committed under `public/vendor/spark/` and are NOT managed by npm — `package.json` only holds vite. Do not add a CSS dependency or move those files without asking.

Never hide content from CSS unless a JS-added root marker proves JS ran: `html.js` is added by an inline script in `components/layouts/head.blade.php`. Hiding unconditionally makes the page unreadable when the bundle fails to load.
