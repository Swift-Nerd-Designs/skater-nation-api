# API Template Changelog

This file is the authoritative upgrade guide for all forks of this template.
Each version entry is structured so Claude can execute the upgrade automatically
via the `/upgrade` skill in the consumer project.

**Template source directory:** the directory containing this file (`client-api/`)
**Consumer project:** any fork of this template (e.g. `odo-api/`)

---

## v2.0 — Full E-commerce + Layered Architecture

### What Changed
Complete overhaul of the architecture from flat query-builder controllers to a layered
Domain/Application/Infrastructure pattern. Adds full shop (products, orders, payments),
customer accounts, multi-user admin, analytics, newsletter subscribers, and dynamic theming.

### Pre-upgrade Checklist
Before starting, verify in the consumer project:
- [ ] `git status` is clean (no uncommitted changes)
- [ ] Database backup has been taken
- [ ] `composer install --no-dev` is up to date
- [ ] `.env` has been backed up

### ══ STEP 1: Skills ══════════════════════════════════════════════════════════

Copy the entire skills directory from the template source:
- Source: `../client-api/.claude/skills/`
- Destination: `.claude/skills/`
- Action: copy all files — `backend-architect.md`, `commit.md`, `deployment.md`,
  `go.md`, `shop-api.md`, `shortcut.md`, `upgrade.md`

### ══ STEP 2: .template-version ════════════════════════════════════════════

Update `.template-version` at project root: set content to `2.0`
(Do this LAST after all other steps pass verification)

### ══ STEP 3: CLAUDE.md ══════════════════════════════════════════════════════

Replace the consumer project's `CLAUDE.md` with the template's `CLAUDE.md`.

⚠️  EXCEPTION: Preserve these consumer-specific values if present:
- Admin cookie name (e.g. `odo_admin_session` vs `jnv_admin_session`)
- Any deployment-specific notes
- Any client-specific route notes

### ══ STEP 4: Filters ════════════════════════════════════════════════════════

**New filter files to add (copy as-is from template):**
- `app/Filters/CustomerAuth.php`

**Update `app/Config/Filters.php`:**
Add to the `$aliases` array:
```php
'customerauth'   => \App\Filters\CustomerAuth::class,
'adminonlyauth'  => \App\Filters\AdminOnlyAuth::class,
```
Do NOT remove or change existing filter entries.

### ══ STEP 5: Domain Layer (NEW — additive) ══════════════════════════════════

The v1 consumer has no `app/Domain/` directory. Create it from scratch.

**Copy entire directories from template (no conflicts):**
- `app/Domain/Shop/`    → all entity classes and repository interfaces
- `app/Domain/Orders/`  → all entity classes and repository interfaces
- `app/Domain/Content/` → NewsletterSubscriber entity, repository interface

**For `app/Domain/Core/`:**
The consumer may already have settings/pages/session logic. Add ONLY the following
new interfaces/entities — do NOT overwrite existing files:
- `app/Domain/Core/AdminUserRepositoryInterface.php`
- `app/Domain/Core/AdminUser.php` (if it exists in template)
Compare the template's `Domain/Core/` with the consumer's before acting.

### ══ STEP 6: Application Layer (NEW — additive) ════════════════════════════

Copy entire directories from template (no conflicts — these don't exist in v1):
- `app/Application/Shop/`    (Commands + Handlers for all shop operations)
- `app/Application/Orders/`  (Commands + Handlers for orders, payments, refunds)

**For `app/Application/Core/`:**
Compare template vs consumer. Add only NEW handlers — do not overwrite existing ones.
New in v2: AdminUserHandlers, multi-user auth handlers.

### ══ STEP 7: Infrastructure — Persistence (additive) ════════════════════════

Copy from template (no conflicts — these repositories don't exist in v1):
- `app/Infrastructure/Persistence/MySqlProductRepository.php`
- `app/Infrastructure/Persistence/MySqlCategoryRepository.php`
- `app/Infrastructure/Persistence/MySqlOrderRepository.php`
- `app/Infrastructure/Persistence/MySqlCustomerRepository.php`
- `app/Infrastructure/Persistence/MySqlCustomerAddressRepository.php`
- `app/Infrastructure/Persistence/MySqlReviewRepository.php`
- `app/Infrastructure/Persistence/MySqlStockRepository.php`
- `app/Infrastructure/Persistence/MySqlAdminUserRepository.php`
- `app/Infrastructure/Persistence/MySqlNewsletterRepository.php`
- `app/Infrastructure/Persistence/MySqlNewsletterSubscriberRepository.php`

⚠️  Do NOT overwrite: `MySqlSettingsRepository.php`, `MySqlPageRepository.php`,
`MySqlAdminSessionRepository.php` — these exist in v1 and may have been customised.
Compare before replacing.

### ══ STEP 8: Infrastructure — Gateways (NEW) ════════════════════════════════

Copy entire new directory:
- `app/Infrastructure/Gateways/` → `PayFastGateway.php`, `OzowGateway.php`

### ══ STEP 9: Infrastructure — Controllers (additive) ══════════════════════

**Copy as-is (entirely new in v2):**
- `app/Infrastructure/Http/Controllers/Admin/Shop/`   (full directory)
- `app/Infrastructure/Http/Controllers/Admin/Analytics.php`
- `app/Infrastructure/Http/Controllers/Admin/Users.php`
- `app/Infrastructure/Http/Controllers/Admin/Documents.php`
- `app/Infrastructure/Http/Controllers/Admin/Newsletters.php`
- `app/Infrastructure/Http/Controllers/Admin/NewsletterSubscribers.php`
- `app/Infrastructure/Http/Controllers/Shop/`         (full directory)
- `app/Infrastructure/Http/Controllers/Content/Documents.php`
- `app/Infrastructure/Http/Controllers/Content/Newsletters.php`
- `app/Infrastructure/Http/Controllers/Content/NewsletterSubscriptions.php`

**Do NOT overwrite (exist in v1, may be customised):**
- `app/Infrastructure/Http/Controllers/Admin/Auth.php`
- `app/Infrastructure/Http/Controllers/Admin/Pages.php`
- `app/Infrastructure/Http/Controllers/Admin/Settings.php`
- `app/Infrastructure/Http/Controllers/Admin/Upload.php`
- `app/Infrastructure/Http/Controllers/Admin/UploadPdf.php`
- `app/Infrastructure/Http/Controllers/BaseController.php`
- `app/Infrastructure/Http/Controllers/Contact.php`
- `app/Infrastructure/Http/Controllers/Content/Pages.php`
- `app/Infrastructure/Http/Controllers/Content/Settings.php`

For the above existing files: compare template vs consumer and MERGE any new helper
methods or constants added in v2 (e.g. `shopOffline()`, `ADMIN_SETTINGS_KEYS` additions).

### ══ STEP 10: Config — Services.php ════════════════════════════════════════

Open template `app/Config/Services.php` side by side with consumer's version.
ADD all new factory methods from the template. Do NOT remove existing ones.

Key new service registrations in v2:
- `productRepository`, `categoryRepository`, `stockRepository`
- `orderRepository`, `customerRepository`, `customerAddressRepository`
- `reviewRepository`, `payfastGateway`, `ozowGateway`
- `lowStockNotifier`, `adminUserRepository`
- All corresponding handler factory methods

### ══ STEP 11: Config — Routes.php ═════════════════════════════════════════

Open template `app/Config/Routes.php` side by side with consumer's version.
ADD new route groups to the consumer. Do NOT remove or change existing routes.

New route groups to add in v2:
- `GET /content/newsletters`, `GET /content/documents`
- `GET /content/newsletter-subscriptions/*` (confirm/unsubscribe)
- `POST /shop/account/register`, `POST /shop/account/login`, `GET /shop/account/me`
- `GET/PUT /shop/account/*` (customer profile, addresses, orders, wishlist)
- `GET /shop/categories`, `GET /shop/products`, `GET /shop/products/(:segment)`
- `POST /shop/checkout`, `GET /shop/orders/(:segment)`
- `POST /shop/payment/payfast/notify`, `POST /shop/payment/ozow/notify`
- `POST /shop/cart/validate`
- `GET/POST/DELETE /shop/products/(:num)/reviews`
- Admin analytics routes under `admin/analytics/`
- Admin shop routes under `admin/shop/`
- Admin user management under `admin/users/`
- Admin newsletter subscriber routes

### ══ STEP 12: Database Migrations ══════════════════════════════════════════

**Check what already exists** in consumer `app/Database/Migrations/` before copying.

v1 consumers already have:
- `2024-01-01-100000_CreateCoreTables.php` (settings, admin_sessions, pages, newsletters, documents)

**Copy from template — all of these are NEW in v2:**
- `2024-01-02-100000_CreateShopTables.php`
- `2024-01-02-110000_CreateShopStockAdjustments.php`
- `2024-01-02-120000_AddLowStockAlertedAt.php`
- `2024-01-03-100000_CreateOrderTables.php`
- `2024-01-03-110000_CreateCustomerSessions.php`
- `2024-01-04-100000_AddTrackingToShopOrders.php`
- `2024-01-05-100000_CreateProductReviews.php`
- `2024-01-05-110000_CreateOrderRefunds.php`
- `2024-01-05-120000_AddPartialRefundStatus.php`
- `2025-01-01-000001_CreateAdminUsers.php`
- `2025-01-01-000002_AddUserToAdminSessions.php`
- `2026-01-01-100000_CreateNewsletters.php`      ← skip if newsletters table already exists
- `2026-01-01-110000_CreateDocuments.php`         ← skip if documents table already exists
- `2026-01-01-120000_CreateNewsletterSubscribers.php`

⚠️  IMPORTANT: If consumer's `CreateCoreTables.php` already creates `newsletters`
and `documents` tables (v1 does), skip the separate 2026-01-01-* migrations for
those tables. Only add the `CreateNewsletterSubscribers.php` migration.

**After copying migrations, run:**
```bash
php spark migrate
```

### ══ STEP 13: Environment Variables ════════════════════════════════════════

Add to `.env` if not present (do NOT overwrite existing values):
```
PAYFAST_MERCHANT_ID=
PAYFAST_MERCHANT_KEY=
PAYFAST_PASSPHRASE=
PAYFAST_TEST=false

OZOW_SITE_CODE=
OZOW_PRIVATE_KEY=
OZOW_API_KEY=
OZOW_TEST=false
```

### ══ STEP 14: Seeds ══════════════════════════════════════════════════════

If a new admin password seeder is needed, copy:
- `app/Database/Seeds/AdminPasswordSeeder.php`

### Post-upgrade Verification

Run these checks after completing all steps:
```bash
# Architecture
php spark migrate:status     # all migrations should show as "ran"

# Public endpoints
curl http://localhost:8080/content/settings   # 200
curl http://localhost:8080/shop/products      # 200 (empty list if no products)
curl http://localhost:8080/shop/categories    # 200

# Admin (expect 401 without auth)
curl http://localhost:8080/admin/me           # 401
curl http://localhost:8080/admin/shop/products # 401

# Customer (expect 401 without auth)
curl http://localhost:8080/shop/account/me   # 401
```

### Breaking Changes / Conflict Zones

These files will likely need manual review due to consumer customisations:

| File | Risk | Action |
|------|------|--------|
| `Admin/Auth.php` | Low | Compare — v2 adds role-based multi-user auth |
| `Admin/Settings.php` | Medium | Merge `ADMIN_SETTINGS_KEYS` — consumer may have custom keys |
| `Content/Settings.php` | Medium | Merge public allowlist — same reason |
| `BaseController.php` | Low | Add new helpers (`shopOffline()`, `bearerToken()`) |
| `Routes.php` | Low | Additive only — just append new groups |
| Admin cookie name | Medium | v2 uses `jnv_admin_session` — change to match consumer's cookie name |

---

## v1.0 — Initial Release

Initial template with CMS, pages, settings, newsletters, documents, admin panel.
No shop, no customer accounts, flat controller architecture.
